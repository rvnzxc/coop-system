<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Generate a simple barcode using Code 128 (basic implementation)
     */
    public static function generateBarcode($code)
    {
        // Simple Code 128 barcode implementation
        $width = 200;
        $height = 80;
        
        // Create barcode pattern (simplified version)
        $pattern = self::generateCode128Pattern($code);
        
        $barcode = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $barcode .= '<rect width="100%" height="100%" fill="white"/>';
        
        $barWidth = $width / count($pattern);
        $x = 0;
        
        foreach ($pattern as $bar) {
            if ($bar == '1') {
                $barcode .= '<rect x="' . $x . '" y="10" width="' . ($barWidth - 1) . '" height="' . ($height - 20) . '" fill="black"/>';
            }
            $x += $barWidth;
        }
        
        // Add the code text below the barcode
        $barcode .= '<text x="' . ($width / 2) . '" y="' . ($height - 5) . '" text-anchor="middle" font-family="Arial" font-size="12" fill="black">' . htmlspecialchars($code) . '</text>';
        
        $barcode .= '</svg>';
        
        return $barcode;
    }
    
    /**
     * Generate a simple pattern for Code 128 (simplified version)
     */
    private static function generateCode128Pattern($code)
    {
        // This is a simplified pattern generator
        // In production, you'd want to use a proper Code 128 library
        $pattern = [];
        
        // Start character
        $pattern = array_merge($pattern, [1,0,1,0,1,1,0,1,1,0]);
        
        // Generate pattern for each character (simplified)
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $charPattern = self::getCharacterPattern($char);
            $pattern = array_merge($pattern, $charPattern);
        }
        
        // Stop character
        $pattern = array_merge($pattern, [1,1,0,0,1,0,1,0,1,1,0]);
        
        return $pattern;
    }
    
    /**
     * Get pattern for a character (simplified)
     */
    private static function getCharacterPattern($char)
    {
        // Simplified pattern generation
        // Each character gets a unique 7-bar pattern
        $patterns = [
            '0' => [1,0,0,1,0,0,1,1,0,1,1],
            '1' => [1,1,0,0,1,0,0,1,0,1,1],
            '2' => [1,0,1,1,0,0,1,0,0,1,1],
            '3' => [1,1,1,0,0,1,0,0,0,1,1],
            '4' => [1,0,0,1,1,0,1,0,0,1,1],
            '5' => [1,1,0,1,1,0,0,1,0,0,1],
            '6' => [1,0,1,1,1,0,0,1,0,0,1],
            '7' => [1,0,0,1,0,1,1,0,0,1,1],
            '8' => [1,1,0,0,1,1,0,1,0,0,1],
            '9' => [1,0,1,1,0,1,0,1,0,0,1],
            'A' => [1,1,0,0,1,0,1,1,0,0,1],
            'B' => [1,0,1,1,0,0,1,1,0,0,1],
            'C' => [1,1,1,0,0,1,0,1,0,0,1],
            'D' => [1,0,0,1,0,1,0,1,1,0,0],
            'E' => [1,1,0,0,1,0,1,0,1,1,0],
            'F' => [1,0,1,1,0,0,1,0,1,1,0],
            'G' => [1,1,1,0,0,1,0,0,1,1,0],
            'H' => [1,0,0,1,1,0,1,0,1,1,0],
            'I' => [1,1,0,1,1,0,0,1,1,0,0],
            'J' => [1,0,1,1,1,0,0,1,1,0,0],
            'K' => [1,0,0,1,0,1,1,0,1,0,0],
            'L' => [1,1,0,0,1,0,1,1,0,1,0],
            'M' => [1,0,1,1,0,0,1,1,0,1,0],
            'N' => [1,1,1,0,0,1,0,1,0,1,0],
            'O' => [1,0,0,1,0,1,0,1,1,1,0],
            'P' => [1,1,0,0,1,0,1,0,1,1,1],
            'Q' => [1,0,1,1,0,0,1,0,1,1,1],
            'R' => [1,1,1,0,0,1,0,0,1,1,1],
            'S' => [1,0,0,1,1,0,1,0,1,1,1],
            'T' => [1,1,0,1,1,0,0,1,1,1,0],
            'U' => [1,0,1,1,1,0,0,1,1,1,0],
            'V' => [1,0,0,1,0,1,1,1,0,1,0],
            'W' => [1,1,0,0,1,0,1,1,1,0,1],
            'X' => [1,0,1,1,0,0,1,1,1,0,1],
            'Y' => [1,1,1,0,0,1,0,1,1,0,1],
            'Z' => [1,0,0,1,1,0,1,1,1,0,1],
        ];
        
        return $patterns[strtoupper($char)] ?? [1,0,1,0,1,0,1,0,1,0,1];
    }
}
