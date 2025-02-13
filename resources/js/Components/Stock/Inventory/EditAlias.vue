<script setup>
import { onMounted, ref, reactive } from "vue";
import axios from "axios";
import { confirmWindowUpdate } from "@/Helper/method";

const props = defineProps({
  aliases: Array,
  stock_id: Number,
});

const select_alias = {
  id: null,
  alias: null,
};

const createAliasObject = reactive({
  stock_id: props.stock_id,
  alias: null,
});
const aliases = ref([]);

const selectToggle = (alias_id) => {
  const alias = aliases.value.find((alias) => alias.id === alias_id);
  if (alias) {
    alias.selected = !alias.selected;
  }

  // 選択されているものは一つの場合選択中オブジェクトに設定
  if (selectedAliasEqualZero) {
    select_alias.id = alias_id;
    select_alias.alias = alias.alias;
  }
  console.log(alias);
};

// 選択されているものが一つか
const selectedAliasEqualZero = () => {
  return aliases.value.filter((alias) => alias.selected).length === 1;
};

// selectedが一つでも選択されているか
const getSelectedAlias = () => {
  return aliases.value.filter((alias) => alias.selected).length > 0;
};

// 編集
const editAlias = () => {
  // 更新対象を取得
  const target_alias = aliases.value.find(
    (alias) => alias.id === select_alias.id
  );
  if (target_alias) {
    if (target_alias.alias === select_alias.alias) {
      alert("編集前と同じです");
    } else {
      // 更新処理
      axios
        .put(route("stock.editAlias"), {
          stock_alias_id: select_alias.id,
          alias: select_alias.alias,
        })
        .then((res) => {
          console.log(res.data);
          confirmWindowUpdate(
            `略名編集が完了しました。\n${target_alias.alias} ------> ${select_alias.alias}`
          );
        })
        .catch((error) => {
          console.log(error);
        });
    }
  } else {
    alert("対象のエイリアスが見つかりません");
  }
};
// 作成
const createAlias = () => {
  const same_alias = aliases.value.find(
    (alias) => alias.alias === createAliasObject.alias
  );
  if (same_alias) {
    alert("このエイリアスは既に登録されています");
  } else {
    // 新規作成処理
    axios
      .post(route("stock.createAlias"), {
        stock_id: createAliasObject.stock_id,
        alias: createAliasObject.alias,
      })
      .then((res) => {
        console.log(res.data);
        confirmWindowUpdate(
          `略名[${createAliasObject.alias}] を追加しました。`
        );
      })
      .catch((error) => {
        console.log(error);
      });
  }
};
// 削除
const deleteAlias = () => {
  // 選択されている全てのIDを取得
  let deleteAliasId = [];
  aliases.value.forEach((alias) => {
    if (alias.selected) {
      deleteAliasId.push(alias.id);
    }
  });

  axios
    .post(route("stock.deleteAlias"), { deleteAliasId: deleteAliasId })
    .then((res) => {
      console.log(res.data);
      confirmWindowUpdate(`略名を削除しました。`);
    })
    .catch((error) => {
      console.log(error);
    });
};
onMounted(() => {
  aliases.value = props.aliases;
});
</script>
<template>
  <div class="px-2 py-2 bg-gray-300">
    <!-- 登録済みの略名を簡易表示 -->
    <div>
      <span
        @click="selectToggle(alias.id)"
        v-for="alias in aliases"
        :key="alias.id"
        :class="{
          'bg-gray-200 text-gray-800 text-xs me-2 px-2.5 py-0.5 rounded-sm dark:bg-gray-700 dark:text-gray-300 transition-all duration-300': true,
          'bg-green-100 text-green-800 text-md font-bold me-2 px-2.5 py-0.5 rounded-sm dark:bg-green-900 dark:text-green-300':
            alias.selected,
        }"
        >{{ alias.alias }}</span
      >
    </div>
    <div
      v-if="selectedAliasEqualZero()"
      class="mt-2 flex justify-start py-2 mb-2"
    >
      <input
        class="appearance-none block w-1/2 bg-gray-50 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
        type="text"
        name="alias"
        id=""
        v-model="select_alias.alias"
      />
      <button
        @click="editAlias"
        class="ml-2 text-sm bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
      >
        編集
      </button>
    </div>
    <div v-else class="mt-2  mb-2">
      <p class="text-xs mt-4 text-gray-600">
        編集したい場合は、登録済みの略名から編集したい略名を選択してください。
      </p>
      <div class="flex justify-start py-2">
        <input
          class="appearance-none block w-1/2 bg-gray-50 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text"
          name="alias"
          id=""
          v-model="createAliasObject.alias"
        />
        <button
          @click="createAlias"
          class="ml-2 text-sm bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
        >
          新規追加
        </button>
      </div>
    </div>

    <div v-if="getSelectedAlias()" class="button_container flex justify-start">
      <button
        @click="deleteAlias"
        class="mr-2 text-sm bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
      >
        削除
      </button>
    </div>
  </div>
</template>
<style scoped lang="scss">
</style>
