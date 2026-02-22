import mysql.connector

try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="", # default xampp password
        database="toko_online"
    )
    cursor = conn.cursor()

    # Read SQL file
    with open(r"c:\xampp\htdocs\sumberjaya\database\create_pembelian_tables.sql", "r") as f:
        sql_content = f.read()

    # Split commands by semicolon, but handle cases where content might have semicolons
    # Ideally should use a proper sql parser, but for this create table script simple split works
    commands = sql_content.split(';')

    for command in commands:
        if command.strip():
            cursor.execute(command)
    
    conn.commit()
    print("Tables created successfully.")

except mysql.connector.Error as err:
    print(f"Error: {err}")
finally:
    if 'conn' in locals() and conn.is_connected():
        cursor.close()
        conn.close()
