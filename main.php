<?php
/*****************************************************************************************************/
/*   ____        _                      __  __  ____   _                           _                 */
/*  / ___|  __ _| | ___   _ _ __ __ _  |  \/  |/ ___| | |    __ _ _   _ _ __   ___| |__   ___ _ __   */
/*  \___ \ / _` | |/ / | | | '__/ _` | | |\/| | |     | |   / _` | | | | '_ \ / __| '_ \ / _ \ '__|  */
/*   ___) | (_| |   <| |_| | | | (_| | | |  | | |___  | |__| (_| | |_| | | | | (__| | | |  __/ |     */
/*  |____/ \__,_|_|\_\\__,_|_|  \__,_| |_|  |_|\____| |_____\__,_|\__,_|_| |_|\___|_| |_|\___|_|     */
/*                                                                                                   */
/*                                             Sakura Minecraft Launcher 樱花启动器 by KasuganoSora  */
/*                                                                                                   */
/*  这是一个开源的软件，您可以在遵守 GPL v3 开源协议的前提下自由使用本软件。                         */
/*                                                                                                   */
/*  作者：KasuganoSora  官网：https://www.moemc.cn/  博客：https://blog.kasuganosora.cn/             */
/*                                                                                                   */
/*****************************************************************************************************/

// 定义全局变量，版本信息、运行目录、系统类型
define("LOCALVERSION", "1.2.0.201");
define("ROOT", str_replace("\\", "/", __DIR__));
define('IS_WIN',strstr(PHP_OS, 'WIN') ? true : false);

// 启动器主类
class SoraOS {
	
	private $downloadProgress;
	private $downloadSize;
	public static $global_user;
	public static $global_pass;
	public static $mainClass;
	public static $minecraftArguments;
	
	// 加载主程序函数
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
		$this::loadConfig();
		$this::home();
		
	}
	
	// 结束主程序函数
	public function onDisable() {
		
		$this::logs("Stopping launcher service...");
		sleep(1);
		$this::logs("正在结束启动器进程...");
		
	}
	
	// 检查更新函数
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
	
	// 程序主页函数
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
			default:
				$this::logs("无效的选择...");
				sleep(2);
				$this::home();
		}
		
	}
	
	// 程序设置页函数
	private function setting() {
		
		$cfg = @json_decode(file_get_contents("Launcher.json"), true);
		echo " —————————————————————————————————————\n\n";
		echo " KasuganoSora 悠穹烟雨启动器\n\n";
		echo " 请选择需要启动的版本，以下为找到的版本列表，输入左侧 ID 即可，留空则使用已保存的配置\n\n";
		$path = ROOT . "/.minecraft/versions/";
        $prevpath = @dirname($path);
        $dir_handle = @opendir($path);
        $versionlist = Array();
        $gid = 1;
		echo "  ID\t名称\n";
        while($file = @readdir($dir_handle)) {
            if($file !== "." && $file !== "..") {
                if(is_dir($path . $file)) {
                    if(file_exists($path . $file . "/" . $file . ".json")) {
                        echo "  " . $gid . "\t" . $file . "\n";
						$versionlist[$gid] = $file;
						$gid++;
                    }
                }
            }
        }
        closedir($dir_handle);
		echo "\n Version> ";
		$version = trim(fgets(STDIN));
		echo "\n";
		if(!file_exists($path . $versionlist[$version] . "/")) {
			echo " 版本不存在或选择错误，请重新选择。";
			sleep(3);
			$this::setting();
		}
		$sversion = $versionlist[$version];
		if(!isset($versionlist[$version])) {
			if(!isset($cfg['version'])) {
				echo "\n 没有已经保存的版本，请重新选择。\n";
				sleep(3);
				$this::home();
			} else {
				$sversion = $cfg['version'];
			}
		}
		echo " 请输入您的 Java 路径，留空则使用已保存的配置，或自动搜索\n\n";
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
		echo "\n 请输入正版账号，如果不想启用正版登录请输入 #，留空则不修改已保存的账号。\n\n";
		echo " User> ";
		$online_user = trim(fgets(STDIN));
		if($online_user == "") {
			if(!isset($cfg['user'])) {
				echo "\n 没有已经保存的账号，正版设置已禁用。\n";
				$online_user = "#";
			} else {
				$online_user = $cfg['user'];
			}
		}
		if($online_user !== "#") {
			echo "\n 请输入正版密码，留空则不修改已保存的密码。\n\n";
			echo " Pass> ";
			$online_pass = trim(fgets(STDIN));
			if($online_pass == "") {
				$online_pass = $cfg['pass'];
			}
		}
		if($online_user == "#" || $online_user == "") {
			echo "\n 请输入游戏名字，留空则不修改已保存的名字。\n\n";
			echo " Name> ";
			$username = trim(fgets(STDIN));
			if($username == "") {
				if(!isset($cfg['user'])) {
					echo "\n 没有已经保存的游戏名字，设置被中断。\n";
					sleep(3);
					$this::home();
				} else {
					$username = $cfg['name'];
				}
			}
		}
		$arr = Array(
			'version' => $sversion,
			'java' => $javapath,
			'ram' => $maxram,
			'user' => $online_user,
			'pass' => $online_pass,
			'name' => $username
		);
		echo "\n 以下是您的配置信息，是否保存？\n\n";
		echo "   选择版本：" . $sversion . "\n\n";
		echo "   Java路径：" . $javapath . "\n\n";
		echo "   最大内存：" . $maxram . "\n\n";
		if($online_user !== "#" && $online_user !== "" && $online_pass !== "") {
			echo "   正版账号：" . $online_user . "\n\n";
			echo "   正版密码：" . str_repeat("*", mb_strlen($online_pass)) . "\n\n";
		}
		if(isset($username)) {
			echo "   游戏名字：" . $username . "\n\n";
		}
		echo " 输入 n 取消保存，输入其他内容保存。\n\n";
		echo " Save> ";
		$save = trim(fgets(STDIN));
		if(strtolower($save) == "n") {
			echo "\n 配置文件未修改。";
			sleep(3);
			$this::home();
		} else {
			@file_put_contents("Launcher.json", json_encode($arr));
			if(isset($username)) {
				$this->global_user = $username;
			}
			echo "\n 配置文件成功保存！";
			sleep(3);
			$this::home();
		}
		
	}
	
	// 加载配置文件
	private function loadConfig() {
		
		$launcher = @json_decode(file_get_contents("Launcher.json"), true);
		if(!$launcher) {
			$this::setting();
		}
		if(isset($launcher['name'])) {
			$this->global_user = $launcher['name'];
		}
		
	}
	
	// 日志记录函数
	public function logs($data, $level = "INFO") {
		
		$dateformart = "[" . date("H:i:s") . " " . $level . "] ";
		echo $dateformart . $data . "\n";
		
	}
	
	// 游戏启动函数
	public function launcher() {
		
		// 如果启动器配置不存在则进入设置页
		if(!file_exists("Launcher.json")) {
			$this::setting();
		}
		
		// 读取启动器设置
		$launcher = json_decode(file_get_contents("Launcher.json"), true);
		if(!file_exists(ROOT . "/.minecraft/versions/" . $launcher['version'] . "/" . $launcher['version'] . ".jar")) {
			$this::logs("客户端不完整，请检查或重新下载客户端。");
		}
		$forgejson = @file_get_contents(ROOT . "/.minecraft/versions/" . $launcher['version'] . "/" . $launcher['version'] . ".json");
		if(empty($forgejson)) {
			$this::logs("Json 文件丢失或损坏，请尝试重新下载客户端。");
			$this::shutdown(1);
		} else {
			$this::logs('Loading Libraries...');
			$readforgejson = json_decode($forgejson, true);
			$libList2 = "";
			$libVersion = $launcher['version'];
			$this->mainClass = $readforgejson['mainClass'];
			if(!isset($readforgejson['minecraftArguments'])) {
				$this->minecraftArguments = "";
				foreach($readforgejson['arguments']['game'] as $args) {
					if(is_string($args)) {
						$this->minecraftArguments .= $args . " ";
					}
				}
			} else {
				$this->minecraftArguments = $readforgejson['minecraftArguments'];
			}
			$assetsIndex = $readforgejson['assets'];
			foreach($readforgejson['libraries'] as $libforge) {
				$this::logs("Loading libraries: " . $libforge['name']);
				$libList2 .= ROOT . "/.minecraft/libraries/" . $this::get_lib_path($libforge['name']) . ";";
				usleep(20000);
			}
			$libList = $libList2;
			if(isset($readforgejson["jar"])) {
				$json = @file_get_contents(ROOT . "/.minecraft/versions/" . $readforgejson["jar"] . "/" . $readforgejson["jar"] . ".json");
				if(empty($json)) {
					$this::logs("Json 文件丢失或损坏，请尝试重新下载客户端。");
					$this::shutdown(1);
				} else {
					$readjson = json_decode($json, true);
					$assetsIndex = $readjson['assets'];
					$libVersion = $readforgejson["jar"];
					if(!file_exists(ROOT . "/.minecraft/libraries/")) {
						@mkdir(ROOT . "/.minecraft/libraries/");
					}
					foreach($readjson['libraries'] as $lib) {
						if(!isset($lib['downloads']['artifact'])) {
							continue;
						}
						$this::logs("Loading libraries: " . $lib['name']);
						if(!file_exists(ROOT . "/.minecraft/libraries/" . $lib['downloads']['artifact']['path'])) {
							// 下载支持库文件
							$this::logs($lib['name'] . " Not Found, downloading...");
							$librarie = @file_get_contents($lib['downloads']['artifact']['url']);
							if(!empty($librarie)) {
								@mkdir(ROOT . "/.minecraft/libraries/" . $this::get_dir($lib['downloads']['artifact']['path']), 0777, true);
								@file_put_contents(ROOT . "/.minecraft/libraries/" . $lib['downloads']['artifact']['path'], $librarie);
								$this::logs('Successful download librarie: ' . $lib['name']);
							} else {
								$this::logs('Failed to download librarie: ' . $lib['name'], 'ERROR');
							}
						} else {
							$libList .= ROOT . "/.minecraft/libraries/" . $lib['downloads']['artifact']['path'] . ";";
						}
						usleep(20000);
					}
				}
			}
		}
		
		// 生成随机 UUID 和 Token
		$uuid = md5(rand(0, 99999) . time() . microtime());
		$token = md5(rand(0, 99999) . time() . microtime());
		$userType = "Legacy";
		
		// 正版登录
		if($launcher['user'] !== "" && $launcher['user'] !== "#" && $launcher['user'] !== null && $launcher['pass'] !== null) {
			$this::logs("Try connect to Mojang auth server...");
			$post_data = json_encode(Array(
				'agent' => Array(
					'name' => 'Minecraft',
					'version' => 1
				),
				'username' => $launcher['user'],
				'password' => $launcher['pass']
			));
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, 'https://authserver.mojang.com/authenticate');
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, Array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($post_data)
			));
			$data = curl_exec($curl);
			if (curl_errno($curl)) {
				$this::logs('Errno' . curl_error($curl));
			}
			curl_close($curl);
			$online_info = @json_decode($data, true);
			if(!$online_info) {
				$this::logs("Failed login to Mojang auth server");
				sleep(3);
				$this::home();
			}
			if(isset($online_info['error'])) {
				$this::logs($online_info['errorMessage']);
				sleep(3);
				$this::home();
			}
			$this->global_user = $online_info['selectedProfile']['name'];
			$uuid = $online_info['selectedProfile']['id'];
			$token = $online_info['accessToken'];
			$this::logs("Login successful, username: " . $this->global_user . ", uuid: " . $uuid);
			$userType = "Mojang";
		}
		
		// 替换启动参数
		$libList .= ROOT . "/.minecraft/versions/" . $libVersion . "/" . $libVersion . ".jar " . $this->mainClass;
		$this->minecraftArguments = str_replace('${auth_player_name}', $this->global_user, $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${version_name}', '"KasuganoSora"', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${game_directory}', ROOT . '/.minecraft/', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${assets_root}', ROOT . '/.minecraft/assets/', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${assets_index_name}', $assetsIndex, $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${auth_uuid}', $uuid, $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${auth_access_token}', $token, $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${user_properties}', '{}', $this->minecraftArguments);
		$this->minecraftArguments = str_replace('${user_type}', $userType, $this->minecraftArguments);
		$cmdjava = str_replace("\\", "/", $launcher['java']);
		if($launcher['java'] == 'java') {
			$cmdjava = 'java';
			if(ISWIN) {
				$cmdjava = 'javaw';
			}
		}
		if(ISWIN) {
			$command = $cmdjava . " -XX:HeapDumpPath=MojangTricksIntelDriversForPerformance_javaw.exe_minecraft.exe.heapdump -XX:+UseG1GC "
			."-XX:-UseAdaptiveSizePolicy -XX:-OmitStackTraceInFastThrow -Xmn128m -Xmx" . $launcher['ram'] . "m -Djava.library.path=" . ROOT . "/.minecraft/versions/KasuganoSora/KasuganoSora-natives"
			. " -Dfml.ignoreInvalidMinecraftCertificates=true -Dfml.ignorePatchDiscrepancies=true -cp " . $libList . " " . $this->minecraftArguments;
		} else {
			$command = $cmdjava . " -Dminecraft.client.jar=" . ROOT . "/versions/" . $launcher['version'] . "/" . $launcher['version'] 
			. ".jar -Dminecraft.launcher.version=7.3.1031 \"-Dminecraft.launcher.brand=Sakura Minecraft Launcher\" -Xincgc -XX:-UseAdaptiveSizePolicy "
			. "-XX:-OmitStackTraceInFastThrow -Xmn128m -Xmx" . $launcher['ram'] . "m -Djava.library.path=" . ROOT . "/versions/" . $launcher['version'] . "/" . $launcher['version'] . "-natives "
			. "-Dfml.ignoreInvalidMinecraftCertificates=true -Dfml.ignorePatchDiscrepancies=true -Duser.home=null -cp " . $libList . " " . $this->minecraftArguments;
		}
		// $LoginService = new LoginService($this->global_user, $this->global_pass);
		// $LoginService->start();
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "r")
		);
		$process = proc_open($command, $descriptorspec, $pipes);
		if(is_resource($process)) {
			while(!feof($pipes[1])) {
				echo fread($pipes[1], 65535);
				echo fread($pipes[2], 65535);
			}
			fclose($pipes[1]);
			fclose($pipes[0]);
			fclose($pipes[2]);
			$return_value = proc_close($process);
		}
		$this::logs("游戏已停止，返回值：" . $return_value);
		if($return_value !== 0) {
			$this::logs("游戏异常退出，返回值：" . $return_value);
			$this::logs("请尝试上网搜索或咨询他人解决问题。");
		}
		sleep(2);
		$this::shutdown(0);
		
	}
	
	// 结束启动器函数
	public function shutdown($status) {
		
		$this::logs("启动器相关服务已停止...");
		exit($status);
		
	}
	
	// 根据 Librarie 名字取目录名
	public function get_dir($path) {
		
		$ex = explode('/', $path);
		$rs = "";
		for($i = 0;$i < count($ex) -1;$i++) {
			$rs .= $ex[$i] . "/";
		}
		return $rs;
		
	}
	
	// 根据 Librarie 名字取文件名
	public function get_lib_path($name) {
		
		$ex = explode(':', $name);
		$rs = str_replace('.', '/', $ex[0]) . '/';
		$rs .= $ex[1] . '/' . $ex[2] . '/' . $ex[1] . '-' . $ex[2] . '.jar';
		return $rs;
		
	}
	
}

// 自动登录游戏支持库
// KasuganoSora 服务器专用
class LoginService extends Thread {
	
	public function __construct($user, $pass){
        $this->user = $user;
		$this->pass = $pass;
		$this->status = true;
    }
	
    public function run() {
		
		$SoraOS = new SoraOS();
		$SoraOS->logs("Starting Auto Login Service...");
		$SoraOS->logs("Set Login Username: " . $this->user);
		if(!file_exists(".minecraft/SoraLoginClient.jar")) {
			$SoraOS->logs("Failed: Auto login libraries not found!");
			return;
		}
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
// 启动主线程
$SoraOS = new SoraOS();
$SoraOS->onEnable();