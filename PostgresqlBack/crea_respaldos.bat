:: cuenta Gmail empaquesanjorgemx@gmail.com S4nj0rg3V14n3y
:: cuenta mandrill empaquesanjorgemx@gmail.com S4nj0rg3V14n3y
:: cuenta Dropbox empaquesanjorgemx@gmail.com S4nj0rg3V14n3y

ROBOCOPY C:\xampp\htdocs\sanjorge C:\Dropbox\sanjorge /E /V /XO /XD C:\xampp\htdocs\sanjorge\application\logs C:\xampp\htdocs\sanjorge\application\media\bascula_snap C:\xampp\htdocs\sanjorge\application\media\polizas /LOG:C:\Users\Administrator\Documents\log_respaldo_app.txt

ROBOCOPY C:\xampp\htdocs\Respaldos C:\Dropbox\Respaldos /E /V /XO /LOG:C:\Users\Administrator\Documents\log_respaldo_resp.txt