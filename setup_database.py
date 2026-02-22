import mysql.connector
import os

def setup_database():
    try:
        # DB Config (hardcoded initial connection to create DB)
        host = "localhost"
        user = "root"
        password = ""
        
        print(f"Connecting to MySQL server at {host}...")
        # Connect without database first to allow creating it
        conn = mysql.connector.connect(
            host=host,
            user=user,
            password=password
        )
        cursor = conn.cursor()

        print("Reading SQL file...")
        with open('database/full_toko_online.sql', 'r') as f:
            sql_script = f.read()

        # Execute SQL script (split by ;)
        print("Executing SQL statements...")
        statements = sql_script.split(';')
        for statement in statements:
            if statement.strip():
                try:
                    cursor.execute(statement)
                    # Iterate over results to clear buffer if any (stored procedures etc)
                    while cursor.next_result(): 
                        pass
                except mysql.connector.Error as err:
                    print(f"Statement skipped/error: {err}")

        conn.commit()
        print("Database setup completed successfully!")
        
        # Verify
        cursor.execute("USE toko_online")
        cursor.execute("SHOW TABLES")
        tables = [table[0] for table in cursor.fetchall()]
        print("\nTables created in 'toko_online':")
        for table in tables:
            print(f"- {table}")
            
        cursor.close()
        conn.close()

    except ImportError:
        print("Error: 'mysql-connector-python' module not found. Please install it or use phpMyAdmin.")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    setup_database()
