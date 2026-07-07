<?php
function ge_audit_csv_safe($value){
    $value=(string)$value;
    $value=str_replace(["
","","
"], ' ', $value);
    $value=trim($value);
    if($value !== '' && in_array(substr($value,0,1), ['=', '+', '-', '@'], true)) $value="'".$value;
    return $value;
}
$rows=audit_rows(20000);
audit_log('audit_csv_download','Audit CSV exported');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="global_energie_audit_logs_'.date('Ymd_His').'.csv"');
$out=fopen('php://output','w');
fputcsv($out,['id','date','user_id','username','action','details','ip','user_agent']);
foreach($rows as $r){ fputcsv($out,[ge_audit_csv_safe($r['id']??''), ge_audit_csv_safe($r['created_at']??''), ge_audit_csv_safe($r['user_id']??''), ge_audit_csv_safe($r['username']??''), ge_audit_csv_safe($r['action']??''), ge_audit_csv_safe($r['details']??''), ge_audit_csv_safe($r['ip']??''), ge_audit_csv_safe($r['user_agent']??'')]); }
fclose($out); exit;
