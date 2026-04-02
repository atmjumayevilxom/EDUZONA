$taskName  = "EDUZONE-QueueWorker"
$workerBat = "c:\xampp\htdocs\games\queue-worker.bat"
$username  = $env:USERNAME

# Remove old task
Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue

# Build task
$action   = New-ScheduledTaskAction -Execute $workerBat -WorkingDirectory "c:\xampp\htdocs\games"
$trigger  = New-ScheduledTaskTrigger -AtLogOn -User $username
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit 0 -MultipleInstances IgnoreNew -StartWhenAvailable

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Force

Write-Host "Task '$taskName' registered for user '$username'" -ForegroundColor Green
Write-Host "It will auto-start at next login." -ForegroundColor Cyan
