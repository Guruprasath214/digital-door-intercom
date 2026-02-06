<?php

namespace QRIntercom\controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use TCPDF;

class ExportController
{
    /**
     * Export visitors data to Excel
     */
    public static function exportToExcel($visitors, $startDate = null, $endDate = null)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set title
            $title = 'Visitors Report';
            if ($startDate && $endDate) {
                $title .= " ($startDate to $endDate)";
            }
            
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add generation date
            $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
            $sheet->mergeCells('A2:H2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Set headers
            $headers = ['S.No', 'Visitor Name', 'Contact', 'Block', 'Floor', 'Resident Name', 'Visit Time', 'Exit Time', 'Status'];
            $headerRow = 4;
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $headerRow, $header);
                $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle($col . $headerRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4F46E5');
                $sheet->getStyle($col . $headerRow)->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            
            // Add data
            $row = 5;
            $sno = 1;
            foreach ($visitors as $visitor) {
                $sheet->setCellValue('A' . $row, $sno++);
                $sheet->setCellValue('B' . $row, $visitor['name']);
                $sheet->setCellValue('C' . $row, $visitor['contact']);
                $sheet->setCellValue('D' . $row, $visitor['block']);
                $sheet->setCellValue('E' . $row, $visitor['floor']);
                $sheet->setCellValue('F' . $row, $visitor['resident_name']);
                $sheet->setCellValue('G' . $row, date('Y-m-d H:i', strtotime($visitor['visit_time'])));
                $sheet->setCellValue('H' . $row, $visitor['exit_time'] ? date('Y-m-d H:i', strtotime($visitor['exit_time'])) : 'N/A');
                $sheet->setCellValue('I' . $row, ucfirst($visitor['status']));
                
                // Alternate row colors
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F1F5F9');
                }
                
                $row++;
            }
            
            // Add borders
            $lastRow = $row - 1;
            $sheet->getStyle('A4:I' . $lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('CBD5E1');
            
            // Auto-size columns
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Output file
            $filename = 'visitors_report_' . date('Y-m-d_His') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to generate Excel: ' . $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Export visitors data to PDF
     */
    public static function exportToPDF($visitors, $startDate = null, $endDate = null)
    {
        try {
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('QR Intercom System');
            $pdf->SetAuthor('Admin');
            $pdf->SetTitle('Visitors Report');
            $pdf->SetSubject('Visitors Management Report');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', 'B', 18);
            
            // Title
            $title = 'Visitors Report';
            if ($startDate && $endDate) {
                $title .= "\n($startDate to $endDate)";
            }
            $pdf->Cell(0, 10, $title, 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $pdf->Ln(5);
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(79, 70, 229); // Indigo color
            $pdf->SetTextColor(255, 255, 255);
            
            $pdf->Cell(10, 7, 'No', 1, 0, 'C', true);
            $pdf->Cell(35, 7, 'Visitor Name', 1, 0, 'C', true);
            $pdf->Cell(28, 7, 'Contact', 1, 0, 'C', true);
            $pdf->Cell(15, 7, 'Block', 1, 0, 'C', true);
            $pdf->Cell(15, 7, 'Floor', 1, 0, 'C', true);
            $pdf->Cell(35, 7, 'Resident', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Visit Time', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Status', 1, 1, 'C', true);
            
            // Table data
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            
            $sno = 1;
            $fill = false;
            foreach ($visitors as $visitor) {
                if ($fill) {
                    $pdf->SetFillColor(241, 245, 249); // Light gray
                } else {
                    $pdf->SetFillColor(255, 255, 255); // White
                }
                
                $pdf->Cell(10, 6, $sno++, 1, 0, 'C', true);
                $pdf->Cell(35, 6, substr($visitor['name'], 0, 25), 1, 0, 'L', true);
                $pdf->Cell(28, 6, $visitor['contact'], 1, 0, 'C', true);
                $pdf->Cell(15, 6, $visitor['block'], 1, 0, 'C', true);
                $pdf->Cell(15, 6, $visitor['floor'], 1, 0, 'C', true);
                $pdf->Cell(35, 6, substr($visitor['resident_name'], 0, 25), 1, 0, 'L', true);
                $pdf->Cell(30, 6, date('Y-m-d H:i', strtotime($visitor['visit_time'])), 1, 0, 'C', true);
                $pdf->Cell(20, 6, ucfirst($visitor['status']), 1, 1, 'C', true);
                
                $fill = !$fill;
            }
            
            // Add summary
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Summary', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            
            $totalVisitors = count($visitors);
            $insideCount = count(array_filter($visitors, fn($v) => $v['status'] === 'inside'));
            $leftCount = count(array_filter($visitors, fn($v) => $v['status'] === 'left'));
            
            $pdf->Cell(60, 5, 'Total Visitors: ' . $totalVisitors, 0, 1);
            $pdf->Cell(60, 5, 'Currently Inside: ' . $insideCount, 0, 1);
            $pdf->Cell(60, 5, 'Checked Out: ' . $leftCount, 0, 1);
            
            // Output PDF
            $filename = 'visitors_report_' . date('Y-m-d_His') . '.pdf';
            $pdf->Output($filename, 'D');
            exit;
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to generate PDF: ' . $e->getMessage()]);
            exit;
        }
    }
}
