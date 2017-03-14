<?php declare(strict_types=1);

const REGEX_DOMAIN = '/^[a-zA-Z0-9\p{L}][a-zA-Z0-9\p{L}-\.]{1,61}[a-zA-Z0-9\p{L}]\.[a-zA-Z0-9\p{L}][a-zA-Z\p{L}-]*[a-zA-Z0-9\p{L}]+$/';
const REGEX_EMAIL = '~^'. '[a-z\d_.+-]+' . '@' . '(?:[a-z\d][a-z\d-]*[a-z\d]\.|[a-z\d]\.)+[a-z]{2,6}' . '$~' . 'ixu';
const REGEX_COUNTRY = '/^[A-Z]{2}$/';

// Init
$KEY = '';
$CSR = '';
$DATA = [];

session_start();

// Controller here
if(count($_GET) > 0) {

	if(req_is_new()) {
		exit_error(401);
		exit;
	}

	$cmd = $_GET['x'];
	switch($cmd) {
		case 'csr':
			do_csr();
			break;
		case 'key-new':
			do_new_key();
			break;
		default:

			// Download?
			if(array_key_exists('d', $_GET)) {
				do_download($_GET['d']);
				exit;
			}
			exit_error(400);
	}
	exit();
// Show form
} else {
	do_init();
}


?><!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
	      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>CSR generator</title>
<style>

	html {
		font: 200 15px Calibri;
		padding: 20px;
	}

	#wa {
		display:flex;
	}

	#in {
		width: 50%;
	}

	#in label {
		display: flex;
		margin: 5px 5px 5px 0;
	}
	#in label span {
		width: 70px;
	}

	#in input:focus {
		outline-offset: 0;
		outline-style: none;
	}
	#in input {
		outline-offset: 0;
		outline: hidden;
		border: 0;
		border-bottom: solid 1px #ccc;
	}
	#in input.error {
		border-bottom-color: red;
	}

	#in .buttons {
		margin: 15px 5px 5px 0;
	}

	#in .buttons a {
		display: inline-block;
		width: auto;
		padding: 8px 15px;
		background-color: #3399ff;
		color: #fff;
		text-decoration:none;
	}
</style>
	<!--suppress JSUnresolvedLibraryURL -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<!-- Clipboard -->
	<!--suppress JSUnresolvedLibraryURL -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.6.0/clipboard.min.js"></script>
</head>
<body>

	<h1>CSR generator</h1>

	<div id="wa">
		<div id="in">
			<h2>Input data</h2>
			<label>
				<span>Domain:</span>
				<input name="d" value="<?php e($DATA['d']); ?>" type="text" required>
			</label>
			<label>
				<span>E-mail:</span>
				<input name="e" value="<?php e($DATA['e']); ?>" type="email" required>
			</label>
			<label>
				<span>Country:</span>
				<input name="c" value="<?php e($DATA['c']); ?>" type="text" required>
			</label>
			<label>
				<span>State:</span>
				<input name="s" value="<?php e($DATA['s']); ?>" type="text" required>
			</label>
			<label>
				<span>Locality:</span>
				<input name="l" value="<?php e($DATA['l']); ?>" type="text" required>
			</label>
			<label>
				<span>Org:</span>
				<input name="o" value="<?php e($DATA['o']); ?>" type="text" required>
			</label>
			<label>
				<span>Org Unit:</span>
				<input name="ou" value="<?php e($DATA['ou']); ?>" type="text" required>
			</label>
			<div class="buttons">
				<a href="?x=csr" id="go">Generate =></a>
			</div>
		</div>

		<div id="out">
			<h2>Private key / <a id="key-renew" href="?x=key-new">renew</a> / <a href="?d=key">download</a> / <a href="#" id="csr-copy" data-clipboard-target="#key">copy</a></h2>
			<pre id="key"><?php e($KEY); ?></pre>
			<h2>CSR <span id="csr-actions">/ <a href="?d=csr">download</a> / <a href="#" id="csr-copy" data-clipboard-target="#csr">copy</a></span></h2>
			<pre id="csr"><?php e($CSR); ?></pre>
		</div>
	</div>
<script>
$(function(){

	// bind renew Key
	$('#key-renew')
		.on('click', function(e) {
			e.preventDefault();
			var $this = $(this);
			var url = $this.attr('href');
			$.get(url, function(data) {
				if(data.key) {
					$('#key').html(data.key);
					$('#csr').html('Regenerate CSR with new key');
				}
			});
	});

	// Validate fields
	var do_validate = function($e) {
		var v = $e.val();
		if(v.trim() === '') {
			$e.addClass('error');
			return false;
		} else {
			$e.removeClass('error');
			return true;
		}
	}
	$('#in').find('input')
		.on('change keyup blur focus', function() { do_validate($(this)) });

	// bind renew Key
	$('#go')
		.on('click', function(e) {
			e.preventDefault();
			var $this = $(this);
			var url = $this.attr('href');

			var data = {};
			var nof = 0;
			$('#in').find('input').each(function(idx, e) {
				var $e = $(e);
				var k = $e.attr('name');
				var v = $e.val();
				if(!do_validate($e)) {
					nof++;
				}
				if(v.trim() === '') {
					nof++;
					$e.addClass('error');
				} else {
					$e.removeClass('error');
				}
				data[k] = v;
			});

			if(0 < nof) {
				console.log('Errors here, dear');
				return;
			}

			$.post(url, data, function(res) {
				var $els = $('#in').find('input');
				if(res.e) { // errors?
					$els.each( function(idx, e) {
						console.log(idx, e);
						var $e = $(e);
						var k = $e.attr('name');
						var err = res.e[k];
						if(err) {
							$e.attr('title', err);
							$e.addClass('error');
						} else {
							e.removeAttribute('title');
						}
					});
				} else {
					$('#csr').html(res.csr);
				}
			});
//			console.log(url, data);
		});

	// bind Clipboard
	new Clipboard('#csr-copy');

});
</script>
</body>
</html>
<?php

{
	function tml_process(string $tml, array $vars): string {
		foreach ($vars as $key => $val) {
			$tml = str_replace('%' . $key . '%', $val, $tml);
		}
		return $tml;
	}

// Read whole file
	function file_rd(string $fnm): string {
		$h = fopen($fnm, 'rb');
		$size = filesize($fnm);
		$text = fread($h, $size);
		fclose($h);
		return $text;
	}

	function file_wr(string $fnm, string $text) {
		$h = fopen($fnm, 'wb');
		fwrite($h, $text);
		fclose($h);
	}

	function e($m) {
		echo $m;
	}

	function debug($x) {
		/** @var array $what */
		$what = func_get_args();
		echo '<pre>';
		foreach ($what as $v) {
			if (is_string($v) || is_numeric($v)) {
				echo $v . PHP_EOL;
			} else {
				/** @noinspection ForgottenDebugOutputInspection */
				var_dump($v);
				/** @noinspection DisconnectedForeachInstructionInspection */
				echo PHP_EOL;
			}
		}
		echo '</pre>';
	}

	function ssl_gen_key() {
		$tmp_key = tempnam('/tmp/', 'key');
		$cmd = 'openssl genrsa -out ' . $tmp_key . ' 2048';
		exec($cmd, $out, $res);
		if ($res === 0) {
			return file_rd($tmp_key);
		}
		return false;
	}

	function req_is_new(): bool {
		return !array_key_exists('data', $_SESSION);
	}

	function exit_json(array $data) {
		header('Content-Type: application/json');
		echo json_encode($data);
		exit;
	}

	function exit_error($code = 400) {
		switch ($code) {
			case 400:
				header('HTTP/1.1 400 Bad Request, dear');
				exit;

			case 404:
				header('HTTP/1.1 404 Not found');
				exit;

			case 401:
				header('HTTP/1.1 401 Unauthorized');
				exit;

			case 403:
			default:
				header('HTTP/1.1 403 Forbidden');
				exit;
		}
	}

	function data_rd() {
		global $DATA, $KEY, $CSR;

		// Default data
		$DATA = [
			'd' => '',
			'e' => '',
			'c' => '',
			's' => '',
			'l' => '',
			'o' => '',
			'ou' => ''
		];

		if (array_key_exists('data', $_SESSION)) {
			$DATA = $_SESSION['data'];
		} else {
			$_SESSION['data'] = $DATA;
		}

		if (array_key_exists('key', $_SESSION)) {
			$KEY = $_SESSION['key'];
		} else {
			$KEY = ssl_gen_key();
			$_SESSION['key'] = $KEY;
		}

		if (array_key_exists('csr', $_SESSION)) {
			$CSR = $_SESSION['csr'];
		}
		if(empty($CSR)) {
			unset($_SESSION['csr']);
			$CSR = '';
		}
	}

	function val_validate($ar, $key, $regex) {

		if (false === array_key_exists($key, $ar)) {
			return false;
		}

		$val = $ar[$key];
		if (empty($val)) {
			return false;
		}

		if (null !== $regex && !preg_match($regex, $val)) {
			return false;
		}

		return true;
	}

	/**
	 * Check data to be ready
	 * @return bool|array True if data is ready to generate, array with errors otherwise
	 */
	function data_validate() {
		global $DATA;

		$err = [];

		if (!val_validate($DATA, 'd', REGEX_DOMAIN)) {
			$err['d'] = 'invalid domain name';
		}

		if (!val_validate($DATA, 'e', REGEX_EMAIL)) {
			$err['e'] = 'invalid address';
		}

		if (!val_validate($DATA, 'c', REGEX_COUNTRY)) {
			$err['c'] = 'invalid country, use 2 chars code';
		}

		if (!val_validate($DATA, 's', null)) {
			$err['s'] = 'invalid state';
		}

		if (!val_validate($DATA, 'l', null)) {
			$err['l'] = 'invalid locality';
		}

		if (!val_validate($DATA, 'o', null)) {
			$err['o'] = 'invalid organization';
		}

		if (!val_validate($DATA, 'ou', null)) {
			$err['ou'] = 'invalid organization unit';
		}

		if (count($err) === 0) {
			return true;
		}

		return $err;
	}

	function do_new_key() {
		ssl_gen_key();
		$KEY = ssl_gen_key();
		$_SESSION['key'] = $KEY;
		unset($_SESSION['csr']);
		exit_json(['key' => $KEY]);
	}


	function do_init() {
		data_rd();
	}

	function do_download(string $what): string {
		global $KEY, $DATA, $CSR;

		data_rd();

		$KEY = $_SESSION['key'];

		$fnm = 'x';
		$text = '';
		switch ($what) {
			case 'key':
				if (empty($KEY)) {
					exit_error(400);
					exit;
				}
				$fnm = empty($DATA['d']) ? 'unknown.private.key' : $DATA['d'] . '.private.key';
				$text = $KEY;
				break;
			case 'csr':
				if (empty($CSR)) {
					exit_error(400);
					exit;
				}
				$fnm = empty($DATA['d']) ? 'unknown.csr' : $DATA['d'] . '.csr';
				$text = $CSR;
				break;
		}

		header('Content-Type: application/octet-stream');
		header('Content-Transfer-Encoding: Binary');
		header('Content-disposition: attachment; filename="' . $fnm . '"');
		echo $text;
		exit;
	} // do_download

	function do_csr() {
		global $DATA, $KEY;

		// Read from session
		data_rd();

		// Read from post
		if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
			$changed = false;
			foreach ($DATA as $k => $v) {
				if (array_key_exists($k, $_POST)) {
					$DATA[$k] = $_POST[$k];
					$changed = true;
				}
			}

			// Special
			$domain = strtolower($DATA['d']);
			if(stripos($domain, 'www.') === 0) {
				$DATA['d'] = substr($DATA['d'],4);
				$changed = true;
			}

			if($changed) {
				$_SESSION['data'] = $DATA;
			}
		}

		$res = data_validate();
		if ($res !== true) {
			exit_json(['e' => $res]);
			return;
		}

//var_export($DATA);
		//region Prepare settings
		$template = <<<TML
[req]
default_bits=2048
prompt=no
default_md=sha256
req_extensions=req_ext
distinguished_name=dn

[dn]
C=%COUNTRY%
ST="%STATE%"
L="%LOCALITY%"
O="%ORG%"
OU="%ORG-UNIT%"
emailAddress=%EMAIL%
CN=www.%DOMAIN%

[req_ext]
subjectAltName=@alt_names

[alt_names]
DNS.1=%DOMAIN%
DNS.2=www.%DOMAIN%
TML;
		$vars = [
			'COUNTRY' => $DATA['c'],
			'STATE' => $DATA['s'],
			'LOCALITY' => $DATA['l'],
			'ORG' => $DATA['o'],
			'ORG-UNIT' => $DATA['ou'],
			'EMAIL' => $DATA['e'],
			'DOMAIN' => $DATA['d']
		];
		$text = tml_process($template, $vars);
//var_export($text);
		$tmp_etc = tempnam('/tmp/', 'e1');
		file_wr($tmp_etc, $text);
		//endregion

		// Store key to file
		$tmp_key = tempnam('/tmp/', 'key');
		file_wr($tmp_key, $KEY);

		// Where CSR will be generated
		$tmp_csr = tempnam('/tmp/', 'csr');

		// Run CSR generation
		$cmd = 'openssl req -new -sha256 -nodes -out ' . $tmp_csr . ' -key ' . $tmp_key . ' -config ' . $tmp_etc . ' 2>&1';
		exec($cmd, $out, $res);
		if ($res !== 0) {
			exit_json(['e' =>
					[
						'exec' => 'Error generating CSR (' . $res . '): ' . implode(PHP_EOL, $out),
						'cmd' => $cmd
					]
				]
			);
			return;
		}

		// Read CSR
		$CSR = file_rd($tmp_csr);
		if (false === $CSR) {
			exit_json(['e' => ['exec' => 'Error reading CSR file ' . $tmp_csr]]);
			return;
		}
		$_SESSION['csr'] = $CSR;

		unlink($tmp_csr);
		unlink($tmp_key);
		unlink($tmp_etc);

		exit_json(['csr' => $CSR]);
	}

}