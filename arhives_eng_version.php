<?php
// Название <input type="file">
$input_name = 'file';
 
// Allowed file extensions.
$allow = array(
	'zip', 'arj', 'rar', 'tar'
);
 
// Prohibited file extensions.
$deny = array(
	'phtml', 'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'phps', 'cgi', 'pl', 'asp', 
	'aspx', 'shtml', 'shtm', 'htaccess', 'htpasswd', 'ini', 'log', 'sh', 'js', 'html', 
	'htm', 'css', 'sql', 'spl', 'scgi', 'fcgi'
);
 
// The directory where the files will be loaded.
$path = __DIR__ . './arhives/';
 
if (isset($_FILES[$input_name])) {
	// Check the directory to load.
	if (!is_dir($path)) {
		mkdir($path, 0777, true);
	}
 
	// Convert array $_FILES into a convenient form for foreach.
	$files = array();
	$diff = count($_FILES[$input_name]) - count($_FILES[$input_name], COUNT_RECURSIVE);
	if ($diff == 0) {
		$files = array($_FILES[$input_name]);
	} else {
		foreach($_FILES[$input_name] as $k => $l) {
			foreach($l as $i => $v) {
				$files[$i][$k] = $v;
			}
		}		
	}	
	
	foreach ($files as $file) {
		$error = $success = '';
 
		// Check for loading errors.
		if (!empty($file['error']) || empty($file['tmp_name'])) {
			switch (@$file['error']) {
				case 1:
				case 2: $error = 'The size of the uploaded file was exceeded.'; break;
				case 3: $error = "The file was only partially received."; break;
				case 4: $error = 'The file has not been downloaded.'; break;
				case 6: $error = "The file has not been loaded - there is no temporary directory."; break;
				case 7: $error = 'The file couldn't be written to disk.'; break;
				case 8: $error = 'PHP extension has stopped loading the file.'; break;
				case 9: $error = 'File could not be loaded - directory does not exist.'; break;
				case 10: $error = 'The maximum allowed file size has been exceeded.'; break;
				case 11: $error = 'This file type is banned.'; break;
				case 12: $error = 'Error copying the file.'; break;
				default: $error = 'File has not been loaded - unknown error.'; break;
			}
		} elseif ($file['tmp_name'] == 'none' || !is_uploaded_file($file['tmp_name'])) {
			$error = 'Failed to download file.';
		} else {
			// Leave only letters, numbers and some symbols in the file name.
			$pattern = "[^a-zа-яё0-9,~!@#%^-_\$\?\(\)\{\}\[\]\.]";
			$name = mb_eregi_replace($pattern, '-', $file['name']);
			$name = mb_ereg_replace('[-]+', '-', $name);
			
			// Since there is a problem with Cyrillic in file names (files become inaccessible).
			// Let's make them transliteration:
			$converter = array(
				'а' => 'a',   'б' => 'b',   'в' => 'v',    'г' => 'g',   'д' => 'd',   'е' => 'e',
				'ё' => 'e',   'ж' => 'zh',  'з' => 'z',    'и' => 'i',   'й' => 'y',   'к' => 'k',
				'л' => 'l',   'м' => 'm',   'н' => 'n',    'о' => 'o',   'п' => 'p',   'р' => 'r',
				'с' => 's',   'т' => 't',   'у' => 'u',    'ф' => 'f',   'х' => 'h',   'ц' => 'c',
				'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',  'ь' => '',    'ы' => 'y',   'ъ' => '',
				'э' => 'e',   'ю' => 'yu',  'я' => 'ya', 
			
				'А' => 'A',   'Б' => 'B',   'В' => 'V',    'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
				'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',    'И' => 'I',   'Й' => 'Y',   'К' => 'K',
				'Л' => 'L',   'М' => 'M',   'Н' => 'N',    'О' => 'O',   'П' => 'P',   'Р' => 'R',
				'С' => 'S',   'Т' => 'T',   'У' => 'U',    'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
				'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',  'Ь' => '',    'Ы' => 'Y',   'Ъ' => '',
				'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
			);
 
			$name = strtr($name, $converter);
			$parts = pathinfo($name);
 
			if (empty($name) || empty($parts['extension'])) {
				$error = 'Invalid file type';
			} elseif (!empty($allow) && !in_array(strtolower($parts['extension']), $allow)) {
				$error = 'Invalid file type';
			} elseif (!empty($deny) && in_array(strtolower($parts['extension']), $deny)) {
				$error = 'Invalid file type';
			} else {
				// In order not to overwrite a file with the same name, we add a prefix.
				$i = 0;
				$prefix = '';
				while (is_file($path . $parts['filename'] . $prefix . '.' . $parts['extension'])) {
		  			$prefix = '(' . ++$i . ')';
				}
				$name = $parts['filename'] . $prefix . '.' . $parts['extension'];
 
				// Move the file to the directory.
				if (move_uploaded_file($file['tmp_name'], $path . $name)) {
					// Then you can save the file name in the database, etc.
					$success = 'File «' . $name . '» has been successfully uploaded.';
				} else {
					$error = 'Failed to upload file.';
				}
			}
		}
		if (!empty($success)) {
			echo '<p>' . $success . '</p><a href="..">Back</a>';		
		} else {
			echo '<p>' . $error . '</p>';
		}
	}
}
?>