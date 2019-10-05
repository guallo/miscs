<?php 

/**
 * $source is the source image path.
 * $target is the target image path.
 * $toAtLeastSizeInBytes must be > 0
 * $toAtMostCompressionLevel must be in range [0.0, 1.0]
 * $maxCompressionError must be in range (0.0, 1.0]
 *
 * Returns an associative array with the keys: 'success', 'last_size' and 
 * 'last_compression_level'.
 *
 * 'success' is true when the $source could be compressed $toAtLeastSizeInBytes
 * into $target or when the $source already has a size <= $toAtLeastSizeInBytes
 * (in that case the $target will be a copy of the $source), otherwise false.
 * 
 * When success, 'last_size' will be the size in bytes of the $target while
 * 'last_compression_level' will be the compression level ([0.0, 1.0]); 
 * or both will be null if the $source already has a size <= $toAtLeastSizeInBytes.
 * 
 * When not success, 'last_size' and 'last_compression_level' will be the values
 * of the last compression iteration if any; otherwise null.
**/
function compressImage_($source, $target, $toAtLeastSizeInBytes, 
                        $toAtMostCompressionLevel=0.7, 
                        $maxCompressionError=0.7) {
    $unsuccessfulResult = array(
        'success' => false, 
        'last_size' => null, 
        'last_compression_level' => null);
    $sourceSize = filesize($source);
    
    if ($sourceSize !== false && $sourceSize <= $toAtLeastSizeInBytes) {
        $data = file_get_contents($source);
        
        if ($data === false || file_put_contents($target, $data) === false) {
            return $unsuccessfulResult;
        }
        
        return array(
            'success' => true, 
            'last_size' => null, 
            'last_compression_level' => null);
    }
    
    $mime = getimagesize($source)['mime'];
    
    if ($mime === 'image/jpeg') {
        $imageCreateFunc = 'imagecreatefromjpeg';
        $imageDumpFunc = 'imagejpeg';
        $translateCompressionFunc = function($c) { return round((1 - $c) * 100); };
    }
    elseif ($mime === 'image/png') {
        $imageCreateFunc = 'imagecreatefrompng';
        $imageDumpFunc = 'imagepng';
        $translateCompressionFunc = function($c) { return round($c * 9); };
    }
    else {
        return $unsuccessfulResult;
    }
    
    $img = $imageCreateFunc($source);
    
    if ($img === false) {
        return $unsuccessfulResult;
    }
    
    $tmpFile = tempnam(sys_get_temp_dir(), '');
    
    if ($tmpFile === false) {
        return $unsuccessfulResult;
    }
    
    $compressionLevel = -$maxCompressionError;
    $lastSize = $size = null;
    $lastCompressionLevel = null;
    
    do {
        $compressionLevel = min($compressionLevel + $maxCompressionError, $toAtMostCompressionLevel, 1);
        $translatedCompressionLevel = $translateCompressionFunc($compressionLevel);
        
        if (!$imageDumpFunc($img, $tmpFile, $translatedCompressionLevel)) {
            break;
        }
        
        clearstatcache(true, $tmpFile);
        
        if (filesize($tmpFile) === false) {
            break;
        }
        
        $lastSize = $size = filesize($tmpFile);
        $lastCompressionLevel = $compressionLevel;
    } while ($size > $toAtLeastSizeInBytes && 
            $compressionLevel < $toAtMostCompressionLevel && 
            $compressionLevel < 1);
    
    unlink($tmpFile);
    
    if ($size !== null && $size <= $toAtLeastSizeInBytes) {
        $success = $imageDumpFunc($img, $target, $translatedCompressionLevel);
    }
    else {
        $success = false;
    }
    
    return array(
        'success' => $success, 
        'last_size' => $lastSize, 
        'last_compression_level' => $lastCompressionLevel);
}

/**
 * $source is the source image path.
 * $target is the target image path.
 * $toAtLeastSizeInBytes must be > 0
 * $toAtMostScalingLevel must be in range (0.0, 1.0]
 * $maxScalingError must be in range (0.0, 1.0]
 * $compressionLevel must be in range [0.0, 1.0]
 *
 * Returns an associative array with the keys: 'success', 'last_size', 
 * 'last_scaling_level', 'last_width' and 'last_height'.
 *
 * 'success' is true when the $source could be scaled $toAtLeastSizeInBytes
 * into $target or when the $source already has a size <= $toAtLeastSizeInBytes
 * (in that case the $target will be a copy of the $source), otherwise false.
 * 
 * When success, 'last_size' will be the size in bytes of the $target while
 * 'last_scaling_level' will be the scaling level ([0.0, 1.0]), 'last_width' and
 * 'last_height' the width and height of the $target also; or they all will be
 * null if the $source already has a size <= $toAtLeastSizeInBytes.
 * 
 * When not success, 'last_size', 'last_scaling_level', 'last_width' and 
 * 'last_height' will be the values of the last scaling iteration if any; 
 * otherwise null.
**/
function scaleImage_($source, $target, $toAtLeastSizeInBytes, 
                    $toAtMostScalingLevel=0.5, 
                    $maxScalingError=0.1, 
                    $compressionLevel=0) {
    $unsuccessfulResult = array(
        'success' => false, 
        'last_size' => null, 
        'last_scaling_level' => null, 
        'last_width' => null, 
        'last_height' => null);
    $sourceSize = filesize($source);
    
    if ($sourceSize !== false && $sourceSize <= $toAtLeastSizeInBytes) {
        $data = file_get_contents($source);
        
        if ($data === false || file_put_contents($target, $data) === false) {
            return $unsuccessfulResult;
        }
        
        return array(
            'success' => true, 
            'last_size' => null, 
            'last_scaling_level' => null, 
            'last_width' => null, 
            'last_height' => null);
    }
    
    $imgInfo = getimagesize($source);
    $mime = $imgInfo['mime'];
    
    if ($mime === 'image/jpeg') {
        $imageCreateFunc = 'imagecreatefromjpeg';
        $imageDumpFunc = 'imagejpeg';
        $translateCompressionFunc = function($c) { return round((1 - $c) * 100); };
    }
    elseif ($mime === 'image/png') {
        $imageCreateFunc = 'imagecreatefrompng';
        $imageDumpFunc = 'imagepng';
        $translateCompressionFunc = function($c) { return round($c * 9); };
    }
    else {
        return $unsuccessfulResult;
    }
    
    $width = $imgInfo[0];
    $height = $imgInfo[1];
    
    if ($width === 0 || $height === 0) {
        return $unsuccessfulResult;
    }
    
    $img = $imageCreateFunc($source);
    
    if ($img === false) {
        return $unsuccessfulResult;
    }
    
    $tmpFile = tempnam(sys_get_temp_dir(), '');
    
    if ($tmpFile === false) {
        return $unsuccessfulResult;
    }
    
    $scaleDimFunc = function($l, $d) { $sd = round((1 - $l) * $d); 
                                        return $sd < 1 ? 1 : $sd; };
    $translatedCompressionLevel = $translateCompressionFunc($compressionLevel);
    $scalingLevel = -$maxScalingError;
    $lastSize = $size = null;
    $lastScalingLevel = null;
    $lastScaledWidth = null;
    $lastScaledHeight = null;
    
    do {
        $scalingLevel = min($scalingLevel + $maxScalingError, $toAtMostScalingLevel, 1);
        $scaledWidth = $scaleDimFunc($scalingLevel, $width);
        $scaledImg = imagescale($img, $scaledWidth);
        
        if ($scaledImg === false) {
            break;
        }
        
        if (!$imageDumpFunc($scaledImg, $tmpFile, $translatedCompressionLevel)) {
            break;
        }
        
        clearstatcache(true, $tmpFile);
        
        if (filesize($tmpFile) === false) {
            break;
        }
        
        $lastSize = $size = filesize($tmpFile);
        $lastScalingLevel = $scalingLevel;
        $lastScaledWidth = $scaledWidth;
        $lastScaledHeight = imagesy($scaledImg);
    } while ($size > $toAtLeastSizeInBytes && 
            $scalingLevel < $toAtMostScalingLevel && 
            $scalingLevel < 1);
    
    unlink($tmpFile);
    
    if ($size !== null && $size <= $toAtLeastSizeInBytes) {
        $success = $imageDumpFunc($scaledImg, $target, $translatedCompressionLevel);
    }
    else {
        $success = false;
    }
    
    return array(
        'success' => $success, 
        'last_size' => $lastSize, 
        'last_scaling_level' => $lastScalingLevel, 
        'last_width' => $lastScaledWidth,
        'last_height' => $lastScaledHeight);
}

/**
 * $source is the source image path.
 * $target is the target image path.
 * $toAtLeastSizeInBytes must be > 0
 * $toAtMostCompressionLevel must be in range [0.0, 1.0]
 * $maxCompressionError must be in range (0.0, 1.0]
 * $toAtMostScalingLevel must be in range (0.0, 1.0]
 * $maxScalingError must be in range (0.0, 1.0]
 *
 * Calls compressImage_($source, $target, $toAtLeastSizeInBytes, 
 *                      $toAtMostCompressionLevel,
 *                      $maxCompressionError)
 * and returns its result if it was success; otherwise returns the result of
 * calling scaleImage_($source, $target, $toAtLeastSizeInBytes, 
 *                      $toAtMostScalingLevel, 
 *                      $maxScalingError, 
 *                      $toAtMostCompressionLevel)
**/
function reduceImageFileSize_($source, $target, $toAtLeastSizeInBytes, 
                            $toAtMostCompressionLevel=0.7, 
                            $maxCompressionError=0.7,
                            $toAtMostScalingLevel=0.5, 
                            $maxScalingError=0.1) {
    $compressionResult = compressImage_($source, $target, $toAtLeastSizeInBytes, 
                                        $toAtMostCompressionLevel,
                                        $maxCompressionError);
    
    if ($compressionResult['success']) {
        return $compressionResult;
    }
    
    $scalingResult = scaleImage_($source, $target, $toAtLeastSizeInBytes, 
                                $toAtMostScalingLevel, 
                                $maxScalingError, 
                                $toAtMostCompressionLevel);
    return $scalingResult;
}

// The default compression values are good for JPEG format. High compression
// level values may result in poor image quality due to the lossy compression
// method of JPEG.
//
// var_dump(reduceImageFileSize_('image.jpg', 'reduced-image.jpg', 700 * 1024));

// With PNG we can use the highest compression level without losing quality at
// the expense of execution time.
//
// var_dump(reduceImageFileSize_('image.png', 'reduced-image.png', 700 * 1024, 1.0, 1.0, 0.8));

?>
