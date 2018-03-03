<?php
define("LOCALVERSION", "1.2.0.201");
define("ROOT", str_replace("\\", "/", __DIR__));
define('IS_WIN',strstr(PHP_OS, 'WIN') ? true : false);
class SoraOS {
	private $downloadProgress;
	private $downloadSize;
	public static $global_user;
	public static $global_pass;
	public static $mainClass;
	public static $minecraftArguments;
	
	public function onEnable() {
		echo file_get_contents(ROOT . "/php/motd.txt");
		sleep(3);
		echo "KasuganoSora Launcher\n";
		echo "Local version: " . LOCALVERSION . "\n";
		sleep(1);
		$this::logs("ROOT DIR SET: " . ROOT);
		$this::logs(IS_WIN ? "WINDOWS SYSTEM: TRUE" : "WINDOWS SYSTEM: FALSE");
		$this::logs("正在启动 KasuganoSora 悠穹烟雨启动器...");
		$this::checkUpdate();
		$this::login();
	}
	
	public function onDisable() {
		$this::logs("Stopping launcher service...");
		sleep(1);
		$this::logs("正在结束启动器进程...");
	}
	
	private function checkUpdate() {
		$this::logs("正在连接到服务器以检查是否有更新...");
		$newVersion = @file_get_contents("https://api.tcotp.cn:4443/Launcher/update/?s=launcher&version=1.4");
		if($newVersion == "") {
			$this::logs("网络连接失败，请检查您的网络是否正常。");
		} elseif($newVersion !== LOCALVERSION) {
			$this::logs("发现启动器新版本：" . $newVersion . " 本地版本：" . LOCALVERSION);
		} else {
			$this::logs("更新检查完毕，已是最新版本。");
		}
	}
	
	private function home() {
		echo " —————————————————————————————————————\n\n";
		echo " KasuganoSora 悠穹烟雨启动器\n\n";
		echo " 请选择您的操作，输入序号后回车\n\n";
		echo " 1.启动游戏  2.游戏设置  3.退出启动器\n\n";
		echo " Input> ";
		$select = trim(fgets(STDIN));
		echo "\n ——————————————————————————————————————\n";
		switch($select) {
			case "1":
				$this::logs("正在启动游戏...");
				$this::launcher();
				break;
			case "2":
				$this::logs("正在加载设置...");
				$this::logs("正在启动设置程序...");
				$this::setting();
				break;
			case "3":
				$this::shutdown(0);
				break;
		}
	}
	
	private function setting() {
		echo " —————————————————————————————————————\n\n";
		echo " KasuganoSora 悠穹烟雨启动器\n\n";
		echo " 请输入您的 Java 路径，留空则使用自动搜索\n\n";
		echo " Java> ";
		$javapath = trim(fgets(STDIN));
		if(!file_exists($javapath) && $javapath !== "") {
			echo "\n Java 路径设置不正确，请检查文件是否存在。";
			sleep(3);
			$this::setting();
		}
		if($javapath == "") {
			$javapath = "java";
		}
		echo "\n";
		echo " 请输入最大内存，单位 MB，留空则使用默认值 1024\n\n";
		echo " Ram> ";
		$maxram = trim(fgets(STDIN));
		if($maxram == "") {
			$maxram = 1024;
		}
		$maxram = intval($maxram);
		if($maxram == 0) {
			echo "\n 最大内存必须是整数且必须大于 0";
			sleep(3);
			$this::setting();
		}
		$arr = Array(
			'java' => $javapath,
			'ram' => $maxram
		);
		@file_put_contents("Launcher.json", json_encode($arr));
		echo "\n 配置文件成功保存！";
		sleep(3);
		$this::home();
	}
	
	private function login() {
		echo " —————————————————————————————————————\n\n";
		echo " KasuganoSora 悠穹烟雨启动器\n\n";
		echo " 欢迎回来，请登录。\n\n";
		echo " User> ";
		$username = trim(fgets(STDIN));
		echo "\n Pass> ";
		$password = trim(fgets(STDIN));
		echo "\n —————————————————————————————————————\n";
		$this::logs("Try to loggin with username: " . $username);
		$handle = @file_get_contents("https://api.tcotp.cn:4443/launcher/login/?version=13014&user=" . $username . "&pass=" . $password);
		switch($handle) {
			case '403':
				$this::logs("Auth Failed, Password Error");
				sleep(1);
				$this::login();
				break;
			case '500':
				$this::logs("Auth Failed, Bad Username");
				sleep(1);
				$this::login();
				break;
			case '502':
				$this::logs("Auth Failed, System suppend login");
				sleep(1);
				$this::login();
				break;
			case '':
				$this::logs("Auth Failed, Network Broken");
				sleep(1);
				$this::login();
				break;
			default:
				if(stristr($handle, '|')) {
					$exc = explode('|', $handle);
					$this::logs("Login Successful, User ID: " . $exc[0]);
					$this->global_user = $exc[0];
					$this->global_pass = $password;
					sleep(1);
					$this::home();
				} else {
					$this::logs("Auth Failed, Unknown Error: " . $handle);
				}
		}
	}
	
	public function logs($data, $level = "INFO") {
		$dateformart = "[" . date("H:i:s") . " " . $level . "] ";
		echo $dateformart . $data . "\n";
	}
	
	public function launcher() {
		if(!file_exists("Launcher.json")) {
			$this::setting();
		}
		if(!file_exists(ROOT . "/.minecraft/versions/KasuganoSora/KasuganoSora.jar")) {
			$this::logs("客户端不完整，请检查或重新下载客户端。");
		}
		$launcher = json_decode(file_get_contents("Launcher.json"), true);
		$forgejson = @file_get_contents(ROOT . "/.minecraft/versions/KasuganoSora/KasuganoSora.json");
		if(empty($forgejson)) {
			$this::logs("Json 文件丢失或损坏，请尝试重新下载客户端。");
			$this::shutdown(1);
		} else {
			$this::logs('Loading Forge Libraries...');
			$readforgejson = json_decode($forgejson, true);
			$libList2 = "";
			$this->mainClass = $readforgejson['mainClass'];
			$this->minecraftArguments = $readforgejson['minecraftArguments'];
			foreach($readforgejson['libraries'] as $libforge) {
				$this::logs("Loading libraries: " . $libforge['name']); //$lib['downloads']['artifact']['path']
				$libList2 .= ROOT . "/.minecraft/libraries/" . $this::get_lib_path($libforge['name']) . ";";
				usleep(50000);
			}
			$libList = $libList2;
		}
		$json = @file_get_contents(ROOT . "/.minecraft/versions/1.8.8/1.8.8.json");
		if(empty($json)) {
			$this::logs("Json 文件丢失或损坏，请尝试重新下载客户端。");
			$this::shutdown(1);
		} else {
			$readjson = json_decode($json, true);
			if(!file_exists(ROOT . "/.minecraft/libraries/")) {
				@mkdir(ROOT . "/.minecraft/libraries/");
			}
			foreach($readjson['libraries'] as $lib) {
				if(!isset($lib['downloads']['artifact'])) {
					continue;
				}
				$this::logs("Loading libraries: " . $lib['name']); //$lib['downloads']['artifact']['path']
				if(!file_exists(ROOT . "/.minecraft/libraries/" . $lib['downloads']['artifact']['path'])) {
					$this::logs($lib['name'] . " Not Found, downloading...");
					$librarie = @file_get_contents($lib['downloads']['artifact']['url']);
					if(!empty($librarie)) {
						//$this::logs("Create dir: " . ROOT . "/.minecraft/libraries/" . $this::get_dir($lib['downloads']['artifact']['path']));
						@mkdir(ROOT . "/.minecraft/libraries/" . $this::get_dir($lib['downloads']['artifact']['path']), 0777, true);
						@file_put_contents(ROOT . "/.minecraft/libraries/" . $lib['downloads']['artifact']['path'], $librarie);
						$this::logs('Successful download librarie: ' . $lib['name']);
					} else {
						$this::logs('Failed to download librarie: ' . $lib['name'], 'ERROR');
					}
				} else {
					$libList .= ROOT . "/.minecraft/libraries/" . $lib['downloads']['artifact']['path'] . ";";
				}
				usleep(50000);
			}
			//$this::logs($libList);
		}
		$uuid = md5(rand(0, 99999) . time() . microtime());
		$libList .= ROOT . "/.minecraft/versions/1.8.8/1.8.8.jar " . $this->mainClass;
		$this->minecraftArguments = str_replace('${auth_player_name}', $this->global_user, $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${version_name}', '"KasuganoSora"', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${game_directory}', ROOT . '/.minecraft/', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${assets_root}', ROOT . '/.minecraft/assets/', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${assets_index_name}', $readjson['assets'], $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${auth_uuid}', $uuid, $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${auth_access_token}', $uuid, $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${user_properties}', '{}', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${user_type}', 'Legacy', $this->minecraftArguments);
		$cmdjava = '"' . $launcher['java'] . '"';
		if($launcher['java'] == 'java') {
			$cmdjava = 'java';
		}
		$command = $cmdjava . " -XX:HeapDumpPath=MojangTricksIntelDriversForPerformance_javaw.exe_minecraft.exe.heapdump -XX:+UseG1GC "
			."-XX:-UseAdaptiveSizePolicy -XX:-OmitStackTraceInFastThrow -Xmn128m -Xmx" . $launcher['ram'] . "m -Djava.library.path=" . ROOT . "/.minecraft/versions/KasuganoSora/KasuganoSora-natives"
			. " -Dfml.ignoreInvalidMinecraftCertificates=true -Dfml.ignorePatchDiscrepancies=true -cp " . $libList . " " . $this->minecraftArguments;
		//$this::logs($command);
		//file_put_contents("launcher.cmd", $command);
		$LoginService = new LoginService($this->global_user, $this->global_pass);
		$LoginService->start();
		//pclose(popen("java -jar \".minecraft/SoraLoginClient.jar\" " . $this->global_user . " " . $this->global_pass, "r"));
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "r")
		);
		$process = proc_open($command, $descriptorspec, $pipes);
		if (is_resource($process)) {
			while(!feof($pipes[0])) {
				echo fread($pipes[1], 65535);
				echo fread($pipes[2], 65535);
			}
			fclose($pipes[1]);
			fclose($pipes[0]);
			fclose($pipes[2]);
			$return_value = proc_close($process);
		}
		$SoraOS->logs("Game is stopped, return value: " . $return_value);
		sleep(2);
		$this::shutdown(0);
	}
	
	public function shutdown($status) {
		$this::logs("服务已停止...");
		exit($status);
	}
	
	public function get_dir($path) {
		$ex = explode('/', $path);
		$rs = "";
		for($i = 0;$i < count($ex) -1;$i++) {
			$rs .= $ex[$i] . "/";
		}
		return $rs;
	}
	
	public function get_lib_path($name) {
		$ex = explode(':', $name);
		$rs = str_replace('.', '/', $ex[0]) . '/';
		$rs .= $ex[1] . '/' . $ex[2] . '/' . $ex[1] . '-' . $ex[2] . '.jar';
		return $rs;
	}
	
	public function curlHeader($ch, $string) {
		$this->downloadSize = strlen($string);
	}
}
class LoginService extends Thread {
	
	public function __construct($user, $pass){
        $this->user = $user;
		$this->pass = $pass;
		$this->status = true;
    }
	
	public function stopLogin() {
		$this->status = false;
	}
	
    public function run() {
		$SoraOS = new SoraOS();
		$SoraOS->logs("Starting Auto Login Service...");
		$SoraOS->logs("Set Login Username: " . $this->user);
        $descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "r")
		);
		$process = proc_open("java -jar \".minecraft/SoraLoginClient.jar\" " . $this->user . " " . $this->pass, $descriptorspec, $pipes);
		while(!feof($pipes[0])) {
			//$SoraOS->logs(fread($pipes[1], 65535));
			//$SoraOS->logs(fread($pipes[2], 65535));
		}
		fclose($pipes[1]);
		fclose($pipes[0]);
		fclose($pipes[2]);
		$return_value = proc_close($process);
		$SoraOS->logs("Server Login Successful");
    }
}
$SoraOS = new SoraOS();
$SoraOS->onEnable();