<?php

if( !function_exists( 'mime_content_type' ) ) {
	function mime_content_type( $filename ) { 
		$ext = substr( strrchr( $filename, '.' ), 1 ); 
		if( empty( $ext ) ) {
			return false; 
		}
		$regex = '/^([\w\+\-\.\/]+)\s+(\w+\s)*(' . $ext . '\s)/i'; 
		$lines = file( 'lib/AFS/mime.types' ); 
		foreach($lines as $line) { 
			if( substr( $line, 0, 1 ) == '#' ) {
				continue; // skip comments 
			}
			$line = rtrim( $line ) . ' '; 
			if( !preg_match( $regex, $line, $matches ) ) {
				continue;
			} 
			return $matches[1]; 
		} 
		return false;
	}
}
?>
