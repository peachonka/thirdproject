program LegacyCSV;

{$mode objfpc}{$H+}

uses
  SysUtils, DateUtils, Process, Classes, StrUtils;

type
  TTelemetryRecord = record
    RecordedAt: TDateTime;
    Voltage: Double;
    Temperature: Double;
    Pressure: Double;
    IsOperational: Boolean;
    ErrorCode: Integer;
    SourceFile: string;
  end;

function GetEnvDef(const name, def: string): string;
var
  v: string;
begin
  v := GetEnvironmentVariable(name);
  if v = '' then
    Result := def
  else
    Result := v;
end;

function RandFloat(minV, maxV: Double): Double;
begin
  Result := minV + Random * (maxV - minV);
end;

function RandInt(minV, maxV: Integer): Integer;
begin
  Result := minV + Random(maxV - minV + 1);
end;

function BoolToStr(b: Boolean): string;
begin
  if b then
    Result := 'TRUE'
  else
    Result := 'FALSE';
end;

function FormatDateTimeISO(dt: TDateTime): string;
begin
  Result := FormatDateTime('yyyy-mm-dd hh:nn:ss', dt);
end;

procedure LogMessage(const msg: string);
var
  logFile: TextFile;
  logPath: string;
begin
  logPath := IncludeTrailingPathDelimiter(GetEnvDef('LOG_DIR', '/app/logs')) + 
             'legacy_' + FormatDateTime('yyyymmdd', Now) + '.log';
  
  AssignFile(logFile, logPath);
  try
    if FileExists(logPath) then
      Append(logFile)
    else
      Rewrite(logFile);
    
    Writeln(logFile, FormatDateTime('yyyy-mm-dd hh:nn:ss', Now) + ' - ' + msg);
  finally
    CloseFile(logFile);
  end;
end;

procedure GenerateCSV(const outDir: string; var filename: string);
var
  csvFile: TextFile;
  recordCount, i: Integer;
  rec: TTelemetryRecord;
begin
  recordCount := RandInt(5, 50); // Случайное количество записей
  filename := 'telemetry_' + FormatDateTime('yyyymmdd_hhnnss', Now) + '.csv';
  filename := IncludeTrailingPathDelimiter(outDir) + filename;
  
  AssignFile(csvFile, filename);
  try
    Rewrite(csvFile);
    
    // Заголовок CSV
    Writeln(csvFile, 'recorded_at,voltage,temperature,pressure,is_operational,error_code,source_file');
    
    // Генерация данных
    for i := 1 to recordCount do
    begin
      rec.RecordedAt := IncSecond(Now, -RandInt(0, 3600)); // Данные за последний час
      rec.Voltage := RandFloat(3.2, 12.6);
      rec.Temperature := RandFloat(-50.0, 80.0);
      rec.Pressure := RandFloat(900.0, 1100.0);
      rec.IsOperational := Random > 0.1; // 90% вероятность TRUE
      rec.ErrorCode := IfThen(rec.IsOperational, 0, RandInt(1, 99));
      rec.SourceFile := ExtractFileName(filename);
      
      // Запись в CSV
      Writeln(csvFile,
        FormatDateTimeISO(rec.RecordedAt) + ',' +
        FormatFloat('0.00', rec.Voltage) + ',' +
        FormatFloat('0.00', rec.Temperature) + ',' +
        FormatFloat('0.0', rec.Pressure) + ',' +
        BoolToStr(rec.IsOperational) + ',' +
        IntToStr(rec.ErrorCode) + ',' +
        rec.SourceFile
      );
    end;
    
    LogMessage('Generated CSV: ' + filename + ' with ' + IntToStr(recordCount) + ' records');
    
  finally
    CloseFile(csvFile);
  end;
end;

function CopyToDatabase(const csvFile, tableName: string): Boolean;
var
  pghost, pgport, pguser, pgpass, pgdb, connStr, copyCmd: string;
  process: TProcess;
  exitCode: Integer;
begin
  Result := False;
  
  pghost := GetEnvDef('PGHOST', 'postgres');
  pgport := GetEnvDef('PGPORT', '5432');
  pguser := GetEnvDef('PGUSER', 'space_user');
  pgpass := GetEnvDef('PGPASSWORD', 'space_pass');
  pgdb := GetEnvDef('PGDATABASE', 'space_db');
  
  // Формируем строку подключения
  connStr := Format('host=%s port=%s user=%s dbname=%s', [pghost, pgport, pguser, pgdb]);
  
  // Команда psql для копирования
  copyCmd := Format(
    '\copy %s(recorded_at, voltage, temperature, pressure, is_operational, error_code, source_file) ' +
    'FROM ''%s'' WITH (FORMAT csv, HEADER true, DELIMITER '','')',
    [tableName, csvFile]
  );
  
  process := TProcess.Create(nil);
  try
    process.Executable := 'psql';
    process.Parameters.Add('-d');
    process.Parameters.Add(connStr);
    process.Parameters.Add('-c');
    process.Parameters.Add(copyCmd);
    
    // Устанавливаем переменную окружения с паролем
    process.Environment.Add('PGPASSWORD=' + pgpass);
    
    process.Options := process.Options + [poWaitOnExit, poUsePipes, poStderrToOutPut];
    process.ShowWindow := swoHIDE;
    
    try
      process.Execute;
      exitCode := process.ExitCode;
      
      if exitCode = 0 then
      begin
        LogMessage('Successfully copied ' + csvFile + ' to database');
        Result := True;
      end
      else
      begin
        LogMessage('Database copy failed with exit code: ' + IntToStr(exitCode));
      end;
      
    except
      on E: Exception do
      begin
        LogMessage('Process execution error: ' + E.Message);
      end;
    end;
    
  finally
    process.Free;
  end;
end;

procedure NotifyRustService(const csvFile: string);
var
  rustHost, rustPort, url, curlCmd: string;
  process: TProcess;
begin
  rustHost := GetEnvDef('RUST_HOST', 'rust-iss');
  rustPort := GetEnvDef('RUST_PORT', '3001');
  
  url := Format('http://%s:%s/api/legacy/notify', [rustHost, rustPort]);
  
  curlCmd := Format(
    'curl -X POST -H "Content-Type: application/json" ' +
    '-d ''{"file_path": "%s", "action": "created"}'' %s',
    [csvFile, url]
  );
  
  process := TProcess.Create(nil);
  try
    process.Executable := 'sh';
    process.Parameters.Add('-c');
    process.Parameters.Add(curlCmd);
    process.Options := [poNoConsole];
    process.Execute;
  finally
    process.Free;
  end;
end;

procedure GenerateExcelReport(const outDir: string; const csvFile: string);
var
  reportFile, cmd: string;
  process: TProcess;
begin
  // Используем Python для конвертации CSV в XLSX
  reportFile := ChangeFileExt(csvFile, '.xlsx');
  
  cmd := Format(
    'python3 -c "import pandas as pd; ' +
    'df = pd.read_csv(''%s''); ' +
    'df[''generated_at''] = pd.Timestamp.now(); ' +
    'df.to_excel(''%s'', index=False, sheet_name=''Telemetry'')"',
    [csvFile, reportFile]
  );
  
  if FileExists('/usr/bin/python3') then
  begin
    process := TProcess.Create(nil);
    try
      process.Executable := 'sh';
      process.Parameters.Add('-c');
      process.Parameters.Add(cmd);
      process.Options := [poWaitOnExit, poNoConsole];
      process.Execute;
      
      if process.ExitCode = 0 then
        LogMessage('Generated Excel report: ' + reportFile);
        
    finally
      process.Free;
    end;
  end;
end;

procedure CleanupOldFiles(const outDir: string; daysToKeep: Integer);
var
  searchRec: TSearchRec;
  filePath: string;
  fileAge: TDateTime;
begin
  if FindFirst(IncludeTrailingPathDelimiter(outDir) + '*.csv', faAnyFile, searchRec) = 0 then
  begin
    repeat
      filePath := IncludeTrailingPathDelimiter(outDir) + searchRec.Name;
      fileAge := FileDateToDateTime(FileAge(filePath));
      
      if DaysBetween(Now, fileAge) > daysToKeep then
      begin
        DeleteFile(filePath);
        LogMessage('Deleted old file: ' + filePath);
        
        // Удаляем соответствующий XLSX файл если есть
        filePath := ChangeFileExt(filePath, '.xlsx');
        if FileExists(filePath) then
          DeleteFile(filePath);
      end;
    until FindNext(searchRec) <> 0;
    
    FindClose(searchRec);
  end;
end;

procedure MainLoop;
var
  outDir, csvFile: string;
  periodSec, iteration: Integer;
  startTime: TDateTime;
begin
  Randomize;
  
  outDir := GetEnvDef('CSV_OUT_DIR', '/app/output');
  periodSec := StrToIntDef(GetEnvDef('GEN_PERIOD_SEC', '300'), 300);
  
  // Создаем директории если их нет
  if not DirectoryExists(outDir) then
    CreateDir(outDir);
  if not DirectoryExists(GetEnvDef('LOG_DIR', '/app/logs')) then
    CreateDir(GetEnvDef('LOG_DIR', '/app/logs'));
  
  iteration := 0;
  LogMessage('Legacy service started. Output directory: ' + outDir);
  
  while True do
  begin
    try
      startTime := Now;
      Inc(iteration);
      
      LogMessage('Starting iteration ' + IntToStr(iteration));
      
      // 1. Генерируем CSV
      GenerateCSV(outDir, csvFile);
      
      // 2. Копируем в БД
      if CopyToDatabase(csvFile, 'telemetry_legacy') then
      begin
        // 3. Уведомляем Rust сервис
        NotifyRustService(csvFile);
        
        // 4. Генерируем Excel отчет (опционально)
        if GetEnvDef('GENERATE_EXCEL', 'false') = 'true' then
          GenerateExcelReport(outDir, csvFile);
      end;
      
      // 5. Очистка старых файлов (раз в 24 часа)
      if iteration mod (86400 div periodSec) = 0 then
        CleanupOldFiles(outDir, 7); // Храним 7 дней
      
      // Логируем время выполнения
      LogMessage('Iteration ' + IntToStr(iteration) + 
                 ' completed in ' + 
                 IntToStr(SecondsBetween(Now, startTime)) + ' seconds');
      
    except
      on E: Exception do
      begin
        LogMessage('ERROR in iteration ' + IntToStr(iteration) + ': ' + E.Message);
        LogMessage('Stack trace: ' + E.StackTrace);
      end;
    end;
    
    // Задержка перед следующей итерацией
    Sleep(periodSec * 1000);
  end;
end;

begin
  // Ловим исключения на верхнем уровне
  try
    MainLoop;
  except
    on E: Exception do
    begin
      Writeln('Fatal error: ', E.Message);
      Halt(1);
    end;
  end;
end.