import requests
from notify import notify_teams
from datetime import datetime

def getUnNotifyData():
    try:
        # APIにリクエストを送信
        response = requests.get('https://akioka.cloud/getUnNotifyData')
        if response.status_code == 200:
            return response.json()
        else:
            response.raise_for_status()
        
    except Exception as e:
        print(f"エラーが発生しました: {str(e)}")
        return []

def complete_notify_queue(notify_queue_id):
    try:
        response = requests.post(
            'https://akioka.cloud/api/completeNotifyQueue',
            json={'id': notify_queue_id},
            timeout=10
        )
        if response.status_code == 200:
            body = response.json()
            return body.get('success', False)
        print(f"通知キュー更新に失敗しました: status={response.status_code}, body={response.text}")
        return False
    except Exception as e:
        print(f"通知キュー更新時にエラーが発生しました: {str(e)}")
        return False

def main():
    print('社内通知実行')
    data = getUnNotifyData()
    title = ''
    msg = ''
    url = ''
    users = []

    for item in data:
        id = item.get('id', '')
        title = item.get('title', '')
        msg = item.get('msg', '') 
        url = item.get('url', '')
        users = item.get('users', [])
        print(users)
        for user in users:
            notify_teams(user, title, msg, url)

        if complete_notify_queue(id):
            with open('log.txt', 'a') as f:
                timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                f.write(f"{timestamp}:{id}\n")
            print(f"通知キュー(ID: {id})を更新しました")
        else:
            print(f"通知キュー(ID: {id})の更新に失敗しました")
    

        

if __name__ == '__main__':
    main()