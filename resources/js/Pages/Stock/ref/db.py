import mysql.connector
from mysql.connector import Error

def execute_select_query(query):
    # MySQLに接続
    mydb = mysql.connector.connect(
      host="akioka.cloud",
      port="3306",
      user="akioka_administrator",
      password="Akiokapass0",
      database="akioka_db"
    )

    mycursor = mydb.cursor()

    # データを取得するクエリを実行
    mycursor.execute(query)

    myresult = mycursor.fetchall()

    for x in myresult:
        print(x)

def execute_insert_query(query):
  try:
      mydb = mysql.connector.connect(
          host="akioka.cloud",
          port=3306,
          user="akioka_administrator",
          password="Akiokapass0",
          database="akioka_db"
      )
      mycursor = mydb.cursor()
      mycursor.execute(query)
      mydb.commit()
      print(f"{mycursor.rowcount} record(s) inserted")
  except Error as e:
      print(f"MySQL Error: {e}")
  except Exception as ex:
      print(f"Unexpected Error: {ex}")
  finally:
      if 'mydb' in locals() and mydb.is_connected():
          mycursor.close()
          mydb.close()

def execute_update_query(query):
  try:
      mydb = mysql.connector.connect(
          host="akioka.cloud",
          port=3306,
          user="akioka_administrator",
          password="Akiokapass0",
          database="akioka_db"
      )
      mycursor = mydb.cursor()
      mycursor.execute(query)
      mydb.commit()
      print(f"{mycursor.rowcount} record(s) updated")
  except Error as e:
      print(f"MySQL Error: {e}")
  except Exception as ex:
      print(f"Unexpected Error: {ex}")
  finally:
      if 'mydb' in locals() and mydb.is_connected():
          mycursor.close()
          mydb.close()
