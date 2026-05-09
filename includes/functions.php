<?php
// includes/functions.php

function icon($name, $class = "")
{
    $path = __DIR__ . "/../assets/icons/" . $name . ".svg";

    if (file_exists($path)) {
        $svg = file_get_contents($path);
        if (!empty($class)) {
            $svg = str_replace('<svg', '<svg class="' . $class . '"', $svg);
        }
        return $svg;
    }
    return ""; // Return empty string if icon not found
}

/*
 * Returns a coloured inline-SVG icon badge for a given file extension.
 * Matches the exact same colors/SVG paths used in the folder browser JS.
 */
function fileTypeIcon(string $ext, int $size = 34): string
{
    $ext = strtolower(trim($ext));

    // colour palette — same as EXT_COLOR / EXT_BG in folder-browser.js
    $palette = [
        'pdf'  => ['bg' => '#fee2e2', 'stroke' => '#dc2626'],
        'doc'  => ['bg' => '#dbeafe', 'stroke' => '#1d4ed8'],
        'docx' => ['bg' => '#dbeafe', 'stroke' => '#1d4ed8'],
        'xlsx' => ['bg' => '#dcfce7', 'stroke' => '#16a34a'],
        'xls'  => ['bg' => '#dcfce7', 'stroke' => '#16a34a'],
        'pptx' => ['bg' => '#ffedd5', 'stroke' => '#ea580c'],
        'ppt'  => ['bg' => '#ffedd5', 'stroke' => '#ea580c'],
        'jpg'  => ['bg' => '#ede9fe', 'stroke' => '#7c3aed'],
        'jpeg' => ['bg' => '#ede9fe', 'stroke' => '#7c3aed'],
        'png'  => ['bg' => '#ede9fe', 'stroke' => '#7c3aed'],
        'gif'  => ['bg' => '#ede9fe', 'stroke' => '#7c3aed'],
        'zip'  => ['bg' => '#fef3c7', 'stroke' => '#b45309'],
        'rar'  => ['bg' => '#fef3c7', 'stroke' => '#b45309'],
        'txt'  => ['bg' => '#f1f5f9', 'stroke' => '#475569'],
    ];

    // SVG path groups — same as ftSvg() in folder-browser.js
    $svgPaths = [
        'pdf'  => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M9 13h6','M9 17h3'],
        'doc'  => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M16 13H8','M16 17H8'],
        'docx' => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M16 13H8','M16 17H8'],
        'xlsx' => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M8 13h2v4H8z','M12 11h2v6h-2z','M16 13h2v4h-2z'],
        'xls'  => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M8 13h2v4H8z','M12 11h2v6h-2z','M16 13h2v4h-2z'],
        'pptx' => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M9 12h4a2 2 0 000-4H9v8'],
        'ppt'  => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M9 12h4a2 2 0 000-4H9v8'],
        'jpg'  => ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
        'jpeg' => ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
        'png'  => ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
        'gif'  => ['M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z','M8.5 10a1.5 1.5 0 103 0 1.5 1.5 0 00-3 0','M21 15l-5-5L5 20'],
        'zip'  => ['M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z','M12 22v-6','M12 2v6'],
        'rar'  => ['M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z','M12 22v-6','M12 2v6'],
        'txt'  => ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6','M16 13H8','M16 17H8'],
    ];

    $fallbackPaths = ['M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z','M14 2v6h6'];
    $fallbackPalette = ['bg' => '#f1f5f9', 'stroke' => '#64748b'];

    $pal   = $palette[$ext]   ?? $fallbackPalette;
    $paths = $svgPaths[$ext]  ?? $fallbackPaths;

    $pathTags = implode('', array_map(fn($d) => "<path d=\"$d\"/>", $paths));

    $br = round($size * 0.21);
    return '<span class="fti-wrap" style="background:' . $pal['bg'] . ';width:' . $size . 'px;height:' . $size . 'px;border-radius:' . $br . 'px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">'
         . '<svg viewBox="0 0 24 24" fill="none" stroke="' . $pal['stroke'] . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:' . round($size * 0.53) . 'px;height:' . round($size * 0.53) . 'px;">'
         . $pathTags
         . '</svg></span>';
}

/*
 * - PDF files (using Smalot\PdfParser)
 * - Word documents DOC/DOCX (using PhpOffice\PhpWord)
 * - Images JPG/PNG/JPEG/GIF (using thiagoalessio\TesseractOCR)
 */
function extractTextFromFile($filePath, $extension)
{
    // Convert extension to lowercase for comparison
    $ext = strtolower(trim($extension));
   
    if (!file_exists($filePath)) {
        return '';
    }
    
    $extractedText = '';
    try {
        // pdf files
        if ($ext === 'pdf') {
            if (class_exists('Smalot\PdfParser\Parser')) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $extractedText = $pdf->getText();
            }
        }
        
        //  WORD FILES DOC, DOCX
      elseif ($ext === 'doc' || $ext === 'docx') {
                if (class_exists('PhpOffice\PhpWord\IOFactory')) {
                    try {
                        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                        $extractedText = '';
                        
                        foreach ($phpWord->getSections() as $section) {
                            foreach ($section->getElements() as $element) {
                                // Try to get text directly
                                if (method_exists($element, 'getText')) {
                                    $text = $element->getText();
                                    if (is_string($text)) {
                                        $extractedText .= $text . ' ';
                                    }
                                }
                                
                                // Handle TextRun and other container elements
                                if (method_exists($element, 'getElements')) {
                                    foreach ($element->getElements() as $child) {
                                        if (method_exists($child, 'getText')) {
                                            $text = $child->getText();
                                            if (is_string($text)) {
                                                $extractedText .= $text . ' ';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Word extraction error: " . $e->getMessage());
                        $extractedText = '';
                    }
                }
        }
        // images png , jpg, jpeg, etc
        elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Check if Tesseract OCR is available
            if (class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
                $tesseract = new \thiagoalessio\TesseractOCR\TesseractOCR($filePath);
                $extractedText = $tesseract->run();
            }
        }
        
        elseif ($ext === 'txt') {
            $extractedText = file_get_contents($filePath);
        }
        
        // other files types
        
    } catch (Exception $e) {
        // If any extraction fails, log the error but don't break the upload
        error_log("Text extraction failed for {$filePath}: " . $e->getMessage());
        $extractedText = '';
    }
    
    // Clean up the extracted text
    $extractedText = trim($extractedText);
    
    // Remove excessive whitespace (multiple spaces, tabs, newlines)
    $extractedText = preg_replace('/\s+/', ' ', $extractedText);
    
    // Limit to 65,535 characters (MySQL TEXT column limit)
    // Use LONGTEXT if you need more
    if (strlen($extractedText) > 65535) {
        $extractedText = substr($extractedText, 0, 65535);
    }
    
    return $extractedText;
}