@SET args_count=0
@FOR %%x in (%*) do @SET /A args_count+=1

@IF %args_count% EQU 0 (
	@CALL ../vendor/bin/tester.bat -s -c ./php.ini .
) ELSE (
	@CALL ../vendor/bin/tester.bat -s -c ./php.ini %*
)