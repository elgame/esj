@ECHO OFF
@setlocal enableextensions
@cd /d "%~dp0"

SET PGPATH=C:\"Program Files"\PostgreSQL\9.1\bin\
SET SVPATH=C:\xampp\htdocs\Respaldos\
SET PRJDB=sanjorge
SET DBUSR=postgres
FOR /F "TOKENS=1,2,3 DELIMS=/ " %%i IN ('DATE /T') DO SET d=%%i-%%j-%%k
FOR /F "TOKENS=1,2,3 DELIMS=: " %%i IN ('TIME /T') DO SET t=%%i%%j%%k

::SET DBDUMP=%PRJDB%_%d%_%t%.sql
SET DBDUMP=respaldo_bd.backup
@ECHO OFF
%PGPATH%pg_dump -Fc -h localhost -p 5432 -U %DBUSR% %PRJDB% > %SVPATH%%DBDUMP%

echo Backup Taken Complete %SVPATH%%DBDUMP%
pause
