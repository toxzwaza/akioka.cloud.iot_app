<?php

namespace App\Http\Controllers;

use App\Models\CalcProductData;
use App\Models\CalcProductLocation;
use App\Models\CalcProductProcess;
use App\Models\CalcProductStatement;
use App\Models\Figure;
use App\Models\FigureImage;
use App\Models\FigureSupplier;
use App\Models\User;
use App\Services\Method;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalcController extends Controller
{
    public function login(Request $request)
    {
        $user_id = $request->user_id;
        $location_id = $request->location_id;

        if (!($user_id && $location_id)) {
            Method::msg('error', 'ログインできませんでした。');
            return redirect()->back();
        }

        $user = User::find($user_id);
        $location = CalcProductLocation::find($location_id);

        session(['user' => ['user_id' => $user_id, 'user_name' => $user->name, 'location_id' => $location_id, 'location_name' => $location->name]]);
        Method::msg('success', 'ログインしました。');
        
        return redirect()->route('calc.search');
    }

    public function logout()
    {
        session()->forget('user');
        Method::msg('success', 'ログアウトしました。');

        return to_route('calc.home');
    }
    //
    public function index()
    {
        $login_user = session('user');

        // ログイン用
        // 製造一課・二課・品証・業務
        $users = User::whereIn('group_id', [2, 3, 4, 6])->where('del_flg', 0)->where('del_flg', 0)->orderby('group_id', 'asc')->get(); //実施者
        $locations  = CalcProductLocation::all(); // 登録場所

        return Inertia::render('Calc/Index', ['login_user' => $login_user, 'users' => $users, 'locations' => $locations]);
    }

    public function search(Request $request)
    {
        $login_user = session('user');
        
        if (!$login_user) {
            return redirect()->route('calc.home');
        }

        $search_keyword = $request->search_keyword ?? [];
        
        // 検索用
        // 得意先一覧
        $search_clients = FigureSupplier::select('id', 'name', 'furi_name')->orderby('furi_name', 'asc')->get();

        // 工程一覧
        $search_processes = CalcProductData::select('process_code', 'process_name')->distinct()->get();

        // 箱Noを取得
        $search_box_numbers = CalcProductData::select('id', 'box_number')->distinct()->get();

        return Inertia::render('Calc/Search', [
            'login_user' => $login_user, 
            'search_clients' => $search_clients, 
            'search_processes' => $search_processes, 
            'search_box_numbers' => $search_box_numbers, 
            'search_keyword' => $search_keyword
        ]);
    }


    public function getProducts(Request $request)
    {
        $msg = null;


        $process_code = $request->process_code;
        $client_name = $request->client_name;
        $client_drawing_number = $request->client_drawing_number;
        $product_name = $request->product_name;
        $box_number = $request->box_number;



        $query = CalcProductData::query();

        if ($box_number) {
            $query->where('box_number', 'like', "%{$box_number}%");
        }
        if ($process_code) {
            $query->where('process_code', $process_code);
        }
        if ($client_name) {
            $query->where('client_name', $client_name);
        }
        if ($client_drawing_number) {
            $query->where('client_drawing_number', 'like', "%{$client_drawing_number}%");
        }
        if ($product_name) {
            $query->where('product_name', 'like', "%{$product_name}%");
        }


        try {
            // client_drawing_numberでグループ化し、box_numberの件数を表示
            $products = $query->get()->groupBy('client_drawing_number')->map(function ($group) {
                $representative = $group->first(); // 各グループの最初の項目を取得
                
                // box_numberの件数をカウント
                $uniqueBoxNumbers = $group->pluck('box_number')->unique();
                $boxCount = $uniqueBoxNumbers->count();
                
                if ($boxCount > 1) {
                    // 複数のbox_numberがある場合は件数を表示
                    $representative->setAttribute('box_number', $boxCount . '件');
                }
                
                return $representative;
            })->values(); // インデックスをリセット
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $products = collect(); // 空のコレクションを返す
        }

        return response()->json(['products' => $products, 'msg' => $msg]);
    }

    public function show($id, Request $request)
    {
        $login_user = session('user');

        $search_keyword = $request->search_keyword;

        $product = CalcProductData::find($id);

        // 図番検索システムから画像取得
        $figure = Figure::where('m_sup_no', $product->client_drawing_number)->first();
        if ($figure) {
            $figure_image = FigureImage::where('figure_id', $figure->id)->where('process_id', 99)->first();
            if ($figure_image) {
                $product->img_path = $figure_image->img_path;
            } else {
                $product->img_path = null;
            }
        }

        // 工程一覧
        $processes = CalcProductProcess::all();

        // 現場ユーザー全取得
        $users = User::whereIn('group_id', [2, 3, 4, 6])->where('del_flg', 0)->where('del_flg', 0)->orderby('group_id', 'asc')->get(); //実施者

        // 登録場所
        $locations  = CalcProductLocation::all();

        // 同一図番でbox_numberが異なるものを取得
        $different_box_numbers = CalcProductData::select('id', 'box_number', 'comp_flg')->where('client_drawing_number', $product->client_drawing_number)->where('box_number', '!=', $product->box_number)->get();
        // dd($different_box_numbers);


        // 検索用
        // 得意先一覧
        $search_clients = CalcProductData::select('client_code', 'client_name')->distinct()->orderby('client_name', 'asc')->get();


        // 工程一覧
        $search_processes = CalcProductData::select('process_code', 'process_name')->distinct()->get();

        // 箱Noを取得
        $search_box_numbers = CalcProductData::select('id', 'box_number')->distinct()->get();




        return Inertia::render('Calc/Show', ['login_user' => $login_user, 'product' => $product, 'processes' => $processes, 'users' => $users, 'locations' => $locations, 'different_box_numbers' => $different_box_numbers, 'search_clients' => $search_clients, 'search_processes' => $search_processes, 'search_box_numbers' => $search_box_numbers, 'search_keyword' => $search_keyword]);
    }
    public function create_new_data($id)
    {
        $figure = Figure::find($id);
        if (!$figure) {
            Method::msg('error', '図番が見つかりませんでした。');
            return redirect()->back();
        }

        // 図番と取引先が一致する棚卸データを取得
        $calc_product_data = CalcProductData::where('client_drawing_number', $figure->m_sup_no)->where('client_name', $figure->supplier_name)->first();
        if (!$calc_product_data) {
            // 棚卸データを新規作成
            $calc_product_data = new CalcProductData();
            $calc_product_data->client_drawing_number = $figure->m_sup_no;
            $calc_product_data->product_name = $figure->name;
            $calc_product_data->client_name = $figure->supplier_name;
            $calc_product_data->abroad_flg = 0;
            $calc_product_data->new_flg = 1;
            $calc_product_data->save();
            Method::msg('success', '新規棚卸データを作成しました。');
        } else {
            Method::msg('success', '既存のデータが見つかりました。');
        }

        return to_route('calc.show', ['id' => $calc_product_data->id]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'calc_product_data_id' => 'required',
            'user_id' => 'required',
            'calc_product_location_id' => 'required',
            'box_number' => 'required|max:255',
            'calc_product_process_id' => 'required',
            'calc_product_status' => 'required',
            'inventory_quantity' => 'required|integer|min:1',
            'memo' => 'nullable|string|max:1000'
        ]);
        $statement_id = $request->statement_id;
        // 編集
        if ($statement_id) {
            $calcProductStatement = CalcProductStatement::find($statement_id);
            Method::msg('success', '棚卸データを編集しました。');
        } else {
            // 棚卸データの保存処理をここに追加
            $calcProductStatement = new CalcProductStatement();
            Method::msg('success', '棚卸データを登録しました。');
        }
        $calc_product_data_id = $request->calc_product_data_id;

        $calcProductStatement->calc_product_data_id = $calc_product_data_id;
        $calcProductStatement->user_id = $request->user_id;
        $calcProductStatement->calc_product_location_id = $request->calc_product_location_id;
        $calcProductStatement->box_number = $request->box_number;
        $calcProductStatement->calc_product_process_id = $request->calc_product_process_id;
        $calcProductStatement->calc_product_status = $request->calc_product_status;
        $calcProductStatement->inventory_quantity = $request->inventory_quantity;
        $calcProductStatement->memo = $request->memo;
        $calcProductStatement->save();


        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $statement_id = $request->statement_id;
        $calcProductStatement = CalcProductStatement::find($statement_id);
        $calcProductStatement->delete();

        Method::msg('success', '棚卸データを削除しました。');
        return redirect()->back();
    }

    public function calc_complete($calc_product_data_id)
    {
        $calc_product_data = CalcProductData::find($calc_product_data_id);
        $calc_product_data->comp_flg = 1;
        $calc_product_data->save();

        Method::msg('success', '完了登録をおこないました。');
        return redirect()->back();
    }
    public function calc_cancel_complete($calc_product_data_id)
    {
        $calc_product_data = CalcProductData::find($calc_product_data_id);
        $calc_product_data->comp_flg = 0;
        $calc_product_data->save();

        Method::msg('success', '完了解除をおこないました。');
        return redirect()->back();
    }

    public function new()
    {
        $suppliers = FigureSupplier::select('id', 'name', 'furi_name')->orderby('furi_name', 'asc')->get();
        $login_user = session('user');

        return Inertia::render('Calc/New', ['suppliers' => $suppliers, 'login_user' => $login_user]);
    }

    public function getCalcProductStatements(Request $request)
    {
        $calc_product_data_id = $request->calc_product_data_id;
        $calcProductStatements = CalcProductStatement::select('calc_product_statements.*', 'users.name as user_name', 'calc_product_processes.name as process_name', 'calc_product_locations.name as location_name')->join('users', 'users.id', 'calc_product_statements.user_id')->join('calc_product_locations', 'calc_product_locations.id', 'calc_product_statements.calc_product_location_id')->join('calc_product_processes', 'calc_product_processes.id', 'calc_product_statements.calc_product_process_id')->where('calc_product_data_id', $calc_product_data_id)->orderBy('calc_product_statements.box_number', 'desc')->get();

        return response()->json($calcProductStatements);
    }


    // エクセルファイル出力用
    public function exportJson()
    {
        $calc_product_dataes = CalcProductData::all();
        foreach ($calc_product_dataes as $calc_product_data) {
            $calc_product_statements = CalcProductStatement::where('calc_product_data_id', $calc_product_data->id)->get();
            $inventory_quantity = 0;
            $memo = "";
            foreach ($calc_product_statements as $calc_product_statement) {
                if (!is_null($calc_product_statement->memo)) {
                    // dd($calc_product_statement->memo);
                    $memo = $memo . "\n" . $calc_product_statement->memo;
                }

                $inventory_quantity += $calc_product_statement->inventory_quantity;
            }
            if ($memo) {
                $memo = ltrim($memo, "\n"); // 最初の改行を削除する
            }
            $calc_product_data->inventory_quantity = $inventory_quantity;
            $calc_product_data->memo = $memo;
        }

        return response()->json($calc_product_dataes);
    }

    public function getFigures(Request $request)
    {

        $supplier_name = $request->supplier_name;
        $m_sup_no = $request->m_sup_no;
        $name = $request->name;

        $query = Figure::query();

        if ($supplier_name) {
            $query->where('supplier_name', 'like', "%$supplier_name%");
        }
        if ($m_sup_no) {
            $query->where('m_sup_no', 'like', "%$m_sup_no%")->orWhere('s_sup_no', 'like', "%$m_sup_no%");
        }
        if ($name) {
            $query->where('name', 'like', "%$name%");
        }
        $line_msg = "検索されました。取引先：{$supplier_name}、図番：{$m_sup_no}、品名：{$name}";

        $figures = $query->get();

        // // 検索内容を保存
        // $figure_search_archive = new FigureSearchArchive();
        // $figure_search_archive->supplier_name = $supplier_name;
        // $figure_search_archive->sup_no = $m_sup_no;
        // $figure_search_archive->name = $name;
        // $figure_search_archive->save();

        // $figure_search_archives = FigureSearchArchive::take(10)->orderby('id', 'desc')->get();

        return ['figures' => $figures];
        // return $figures;
    }
}
