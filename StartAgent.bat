@echo off
setlocal enabledelayedexpansion

cd Agent

if "%~1" neq "" (
    if "%~2" neq "" (
        set "ARGS=--username=%~1 --password=%~2"
    ) else (
        set "ARGS="
    )
) else (
    set "ARGS="
)

if "!ARGS!"=="" (
    mvn spring-boot:run
) else (
    mvn spring-boot:run "-Dspring-boot.run.arguments=!ARGS!"
)

endlocal