<?php
session_start();
require '../vendor/autoload.php';
include "../include/db.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$teacherName   = $_SESSION['teacher_name']   ?? '';
$teacherSub    = $_SESSION['teacher_subject']?? '';
$teacherSec    = $_SESSION['teacher_section']?? '';

if(!$teacherName){
    header("Location: ../auth/login.php");
    exit();
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$row = 1;

$sheet->setCellValue('A'.$row, 'Instructor: '.$teacherName); $row++;
$sheet->setCellValue('A'.$row, 'Subject: '.$teacherSub); $row++;
$sheet->setCellValue('A'.$row, 'Section: '.$teacherSec); $row+=2;

$headers = [
'PC NO','STUDENT NAME','COURSE','YEAR','SECTION',
'COMLAB','PC STATUS','TIME IN','TIME OUT','DATE'
];

$col='A';

foreach($headers as $h){

$sheet->setCellValue($col.$row,$h);
$sheet->getStyle($col.$row)->getFont()->setBold(true);

$col++;

}

$row++;

$course='';
$year='';
$section='';

if($teacherSec){

$parts = explode("-",$teacherSec);

if(count($parts)==2){

$course = trim($parts[0]);
$yearSection = trim($parts[1]);

$yearNumber = preg_replace('/[^0-9]/','',$yearSection);
$section    = preg_replace('/[^A-Za-z]/','',$yearSection);

$yearMap=[
"1"=>"1ST YEAR",
"2"=>"2ND YEAR",
"3"=>"3RD YEAR",
"4"=>"4TH YEAR"
];

$year = $yearMap[$yearNumber] ?? "";

}

}

if($course && $year && $section){

$stmt = $conn->prepare("
SELECT
a.pc_no,
s.student_name,
s.course,
s.year,
s.section,
a.comlab_no,
a.pc_status,
a.time_in,
a.time_out,
a.date
FROM attendance a
JOIN students s ON s.student_id=a.student_id
WHERE s.course=?
AND s.year=?
AND s.section=?
ORDER BY a.id DESC
");

$stmt->bind_param("sss",$course,$year,$section);

$stmt->execute();

$res=$stmt->get_result();

while($r=$res->fetch_assoc()){

$sheet->setCellValue('A'.$row,$r['pc_no']);
$sheet->setCellValue('B'.$row,$r['student_name']);
$sheet->setCellValue('C'.$row,$r['course']);
$sheet->setCellValue('D'.$row,$r['year']);
$sheet->setCellValue('E'.$row,$r['section']);
$sheet->setCellValue('F'.$row,$r['comlab_no']);
$sheet->setCellValue('G'.$row,$r['pc_status'] ?? '');

$sheet->setCellValue(
'H'.$row,
$r['time_in'] ? date('h:i A',strtotime($r['time_in'])) : ''
);

$sheet->setCellValue(
'I'.$row,
$r['time_out'] ? date('h:i A',strtotime($r['time_out'])) : ''
);

$sheet->setCellValue(
'J'.$row,
$r['date'] ? date('M d, Y',strtotime($r['date'])) : ''
);

$row++;

}

$stmt->close();

}

foreach(range('A','J') as $c){
$sheet->getColumnDimension($c)->setAutoSize(true);
}

$filename="Attendance_Report.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer=new Xlsx($spreadsheet);
$writer->save('php://output');

exit;