<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//require_once APPPATH . 'third_party/API/Mq.php';

use Misc\Http\Client\GraphClient;
use Misc\Api;

class EI_Controller extends CI_Controller {

    protected $account_info;
    protected $CI;
    public $base_url;
    public $session_token = "";
    private $_config = array();
    protected $root_folder = "";
    protected $data = array();
    protected $event_key = "";
    protected $token;
    protected $content = '';
    protected $memcache;
    protected $memcache_status;
    public $userinfo;

    public function __construct() {
        parent::__construct();
        $this->base_url = $this->config->config['base_url'];
        $this->load->library('Mobile_Detect');
        $this->_config["memcache"] = array("host" => "127.0.0.1", "port" => 11211);
    }

    public function write_log($message) {
        $this->config->load('mq_setting');
        $mq_config = $this->config->item('mq');
        $config['routing'] = $mq_config['mq_routing'];
        $config['exchange'] = $mq_config['mq_exchange'];
        $ddd = API_Mq::push_rabbitmq($config, $message);
        return $ddd;
    }

    public function is_tester($mobo_id, $service_id) {
        $data = $this->get_mobo_account($mobo_id);
        $mobo = $this->parse_mobo($data, $service_id);
        if (!empty($mobo)) {
            $phone = $mobo[0]["phone"];
            return (strpos($phone, "19006611") !== false && strpos($phone, "19006611") == 0);
        }
        return false;
    }

    public function map_alias($app) {
        switch ($app) {
            case "10000":
                return "banca";
            case "1001":
                return "doden";
            case "1003":
                return "bancatour";
        }
    }

    public function hash_secret_key($app = 0) {
		
        switch ($app) {			
            case "10000":
                return "2K4ZRMSYM3W3D4YY";
            case "10001":
                //add tam ban ca
                return "2K4ZRMSYM3W3K4AY";
            case "10002":
                return "WLFHE5FWM4BSIIXU";
            case "10003":
                //add tam ban ca
                return "RMKZRMSYM3W3KYTX";
			case "10004":
                //add tam ban ca
                return "LONGRMSYM3W3KMON";
            default:
                return "";
        }
    }

    public function time_unique($ticker = 1) {
        $second = round(intval(date("s", time())) / $ticker);
        return (date("YmdHi", time()) . $second);
    }

    public function convert_date($create_time) {
        $mil = $create_time;
        $seconds = (int) ($mil / 1000);
        $date = date("Y-m-d H:i:s", $seconds);
        $currentTime = DateTime::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:s", time()));
        $createTime = DateTime::createFromFormat("Y-m-d H:i:s", $date);
        $interval = $currentTime->diff($createTime);
        $day = $value;
        $markday = $interval->d + ($interval->h / 24) + (($interval->i / 60) / 24) + ((($interval->s / 60) / 60) / 24);
        return $markday;
    }

    public function hidden_char($str, $index) {
        $len = strlen($str);
        if ($len == 0)
            return $str;
        else {
            if ($len <= $index) {
                return $str;
            } else {
                $returnstr = "";
                for ($i = 0; $i < ($len - $index) - 1; $i++) {
                    $returnstr .= "*";
                }
                return $returnstr . substr($str, ($len - $index) - 1);
            }
        }
    }

    protected function isDevice() {
        $mobile = new Mobile_Detect();
        if ($mobile->isTablet()) {
            if ($mobile->is("iOS")) {
                return "iPad - OS " . $mobile->version('iPad');
            } else {
                return "Android - OS " . $mobile->version('Android');
            }
        } else if ($mobile->isMobile()) {
            if ($mobile->is("iOS")) {
                return "iPhone - OS "; // . $mobile->version('iPhone');
            } else {
                return "Android - OS " . $mobile->version('Android');
            }
        } else {
            return "Web Browser";
        }
    }

    public function mapping($sid = 0) {
        switch ($sid) {
            case "1000":
                return "https://sev.banca888.net";
            case "1001":
                return "https://sev.bai88.net";
        }
    }

    private $_control = 'inside';
    private $_getinfo_func = 'search_graph';
    private $_app = 'skylight';
    private $_api_url = 'https://graph.addgold.net/';
    private $_api_cs = 'http://s4-graph.mobo.vn/';
    protected $service_id = 101;
    protected $_key = "QEOODZHBTPE6ZJI7";

    public function verify_access_token($app_id,$access_token) {

        $graphApplication = new Api(new GraphClient());
        $graphApplication->getHttpClient()->setApp($app_id);
        $graphApplication->getHttpClient()->setSecret($this->hash_secret_key($app_id));

        $result = $graphApplication->call("/game/verify_access_token", "GET", array('access_token'=>$access_token))->getContent();
        if ($result["code"] === 500010) {
            return $result["data"];
        } else {
            return false;
        }
    }

    public function get_mobo_account($mobo_id) {
        $this->load->library('GeneralOTPCode');
        $otp = GeneralOTPCode::getCode($secret);
        $params['control'] = $this->_control;
        $params['func'] = $this->_getinfo_func;
        $params['app'] = $this->_app;
        $params['otp'] = $otp;
        $params['mobo'] = $mobo_id;
        $needle = array('control', 'func', 'access_token', 'user_agent', 'app', 'otp');
        $params['token'] = md5(implode('', $params) . $this->_key);
        $url = $this->_api_url . '?' . http_build_query($params);
        if ($_SERVER['REMOTE_ADDR'] == "127.0.0.1") {
            $response = '{"code":900000,"desc":"SEARCH_GRAPH_SUCCESS","data":{"114":[{"mobo_id":"128147013","mobo_service_id":"1141502494438423506","fullname":"S\u00e1u Ngh\u0129a","device_id":"e0d97aa278c0faf39c743f1d83121cac02eca72e321dsf","channel":"1|no-cookie","date_create":"2015-05-29 16:05:56","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}],"108":[{"mobo_id":"128147013","mobo_service_id":"1081501741760312010","fullname":"S\u00e1u Ngh\u0129a","device_id":"1646-e619-0eb1-a8d1-0000-0030-00f0-0ca8","channel":"1|me|1.0.0|File|msv_1_file","date_create":"2015-05-21 08:42:26","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}],"107":[{"mobo_id":"128147013","mobo_service_id":"1071500395737754192","fullname":"S\u00e1u Ngh\u0129a","device_id":"1646-e619-0eb1-a8d1-0000-0030-00f0-0ca8","channel":"1|me|1.0.0|File|msv_1_file","date_create":"2015-05-06 12:15:54","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"},{"mobo_id":"128147013","mobo_service_id":"1071500395973232102","fullname":"S\u00e1u Ngh\u0129a","device_id":"1646-e619-0eb1-a8d1-0000-0030-00f0-0ca8","channel":"1|me|1.0.0|File|msv_1_file","date_create":"2015-05-06 12:15:55","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}],"106":[{"mobo_id":"128147013","mobo_service_id":"1061495878891844701","fullname":"S\u00e1u Ngh\u0129a","device_id":"34cf08865d7ed7495a322ba23c0afd8d9b0e6482","channel":"1|me|1.0.2|Ent|msv_1","date_create":"2015-03-17 15:34:39","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}],"105":[{"mobo_id":"128147013","mobo_service_id":"1051490237959950717","fullname":"S\u00e1u Ngh\u0129a","device_id":"1646-e619-0eb1-a8d1-0000-0030-00f0-0ca8","channel":"empty","date_create":"2015-01-14 09:12:35","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"},{"mobo_id":"128147013","mobo_service_id":"1051496965128263014","fullname":"Giang H\u1ed3","device_id":"3c534d15cdbd360b353483a8c1f044e4b3886b6c","channel":"2|me|1.0.4|Appstore|msv_2","date_create":"2015-03-29 15:19:55","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}],"103":[{"mobo_id":"128147013","mobo_service_id":"1031504929525518311","fullname":"S\u00e1u Ngh\u0129a","device_id":"2b18-d5b3-6a4e-3e4f-9f6c-0c17-a1c8-bfc6","channel":"1|mobo|1.0.0","date_create":"2014-12-31 11:43:08","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}],"102":[{"mobo_id":"128147013","mobo_service_id":"1021492806371063290","fullname":"server 2","device_id":"933957fe427b847ce321c53f4878044012b25577","channel":"2|me|2.0.3|Appstore","date_create":"2015-02-11 17:35:52","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"},{"mobo_id":"128147013","mobo_service_id":"1021492771838807056","fullname":"S\u00e1u Ngh\u0129a","device_id":"933957fe427b847ce321c53f4878044012b25577","channel":"2|me|2.0.3|Appstore","date_create":"2015-02-11 08:27:00","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}],"101":[{"mobo_id":"128147013","mobo_service_id":"1011489789116512995","fullname":"S\u00e1u Ngh\u0129a","device_id":"34cf08865d7ed7495a322ba23c0afd8d9b0e6482","channel":"3|me|1.0.0","date_create":"2015-01-09 10:18:36","status":"actived","phone":"0909968087","facebook_id":null,"facebook_orgin_id":"","status_mobo":"actived"}]},"message":"SEARCH_GRAPH_SUCCESS"}';
        } else {
            $response = $this->get($url);
        }
        return json_decode($response, TRUE);
    }

    public function parse_mobo($data, $service_id = "") {
        if ($data["code"] == 900000) {
            if (empty($service_id))
                return $data["data"];
            else
                return $data["data"][$service_id];
        }
        return null;
    }

    public function get($url) {
        if (empty($url)) {
            return false;
        }
        return $this->request('GET', $url, 'NULL');
    }

    public function sign_http($params) {
        $curtoken = $params["token"];
        unset($params["token"]);
        if (isset($params["sign"])) {
            $resign = $params["sign"];
            $token = md5(implode('', $params) . $this->get_secret_key_by_game_id($resign));
            $params["token"] = $token;
        }
        return $params;
    }

    public function resign_http($params) {
        $curtoken = $params["token"];
        unset($params["token"]);
        if (isset($params["resign"])) {
            $resign = $params["resign"];
            $token = md5(implode('', $params) . $this->get_secret_key_by_game_id($resign));
            $params["token"] = $token;
        }
        return $params;
    }

    public function rebuild_http($url, $params) {
        $response_url = "";
        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        $curtoken = $params["token"];
        unset($params["token"]);
        if ($query == true && !isset($query["cr"])) {
            if (isset($query["sign"])) {
                $sign = $query["sign"];
                $params = array_merge($query, $params);
                $token = md5(implode('', $params) . $this->get_secret_key_by_game_id($sign));
                $params["token"] = $token;
            } else {
                $params = array_merge($query, $params);
                $token = md5(implode('', $params) . $this->private_key);
                $params["token"] = $token;
            }
        } else {
            if (isset($query["cr"]))
                unset($query["cr"]);
            $params = array_merge($query, $params);
            $params["token"] = $curtoken;
        }
        return $parts["scheme"] . "://" . $parts["host"] . $parts["path"] . "?" . http_build_query($params);
    }

    protected function verify_uri() {
        $params = $this->input->get();
        $token = trim($params['token']);
        unset($params['token']);
        if (isset($params["no"]) && $params["no"] == true) {
            unset($params['no']);
            unset($params['p']);
            $serect = $this->get_secret_key_by_game_id($params["game_id"]);
            unset($params['event_id']);
            unset($params['game_id']);
            unset($params["force_id"]);
        } else {
            $serect = $this->private_key;
        }
        $valid = md5(implode('', $params) . $serect);
        if ($valid === $token) {
            return true;
        } else {
            return false;
        }
    }

    protected function verify_uri_session($params = array()) {
        //var_dump($params); die;
        if (empty($params) === TRUE) {
            $params = $this->input->get();
        }
        $token = trim($params['token']);
        unset($params['token']);
        $valid = md5(implode('', $params) . $this->private_key);
        if ($valid === $token) {
            return true;
        } else {
            return false;
        }
    }

    protected function create_session_token() {
        $params = $this->input->get();
        $valid = md5(implode('', $params) . $this->private_key);
        $_SESSION["session_token"] = $valid;
        return $valid;
    }

    protected function validate_login($key) {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        $session_id = session_id();
        $session_save = $this->getMemcache('session_login' . $this->idgame . $key);
        if (empty($session_save)) {
            return true;
        }
        return ($session_id === $session_save);
    }

    protected function store_login($key, $cachetime = 3600) {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        $key = 'session_login' . $this->idgame . $key;
        return $this->saveMemcache($key, session_id(), $cachetime);
    }

    private function unique_id($id) {
        $memcache = new Memcache;
        $host = $this->_config["memcache"]["host"];
        $port = $this->_config["memcache"]["port"];
        $status_memcache = @$memcache->connect($host, $port);
        if ($status_memcache == true) {
            $key = md5($id);
            $value = $memcache->get($key);
            if (empty($value)) {
                $memcache->set($key, $id, false, 3600);
                $memcache->close();
                return false;
            } else {
                return true;
            }
        } else {
            //kiem tra db 
            return false;
        }
    }

    protected function saveMemcache($key, $value, $cachetime = 3600) {
        $host = $this->_config["memcache"]["host"];
        $port = $this->_config["memcache"]["port"];
        $memcache = new Memcache();
        $status = @$memcache->connect($host, $port);
        if ($status == true) {
            $mkey = md5($key);
            $memcache->set($mkey, $value, false, $cachetime);
            $memcache->close();
            return true;
        }
        return false;
    }

    protected function getMemcache($key) {
        $host = $this->_config["memcache"]["host"];
        $port = $this->_config["memcache"]["port"];
        $memcache = new Memcache();
        $status = @$memcache->connect($host, $port);
        if ($status == true) {
            $mkey = md5($key);
            $memcache->getVersion();
            $value = $memcache->get($mkey);
            $memcache->close();
            return $value;
        }
        return null;
    }

    protected function init_settings($root_dir = "") {
        $this->root_folder = $root_dir;
    }

    protected function render($view) {
        $this->data["controller"] = $this;
        echo $this->load->view("{$this->root_folder}/{$view}", $this->data, true);
        exit();
    }

    protected function render_sub($view) {
        $this->data["controler"] = $this;
        return $this->load->view("{$this->root_folder}/{$view}", $this->data, true);
    }

    protected function get_user_info($params) {
        $params["control"] = "user";
        $params["func"] = "get_account_info";
        return $this->_call($params);
    }

    protected function get_user_all_level($params) {
        $params["control"] = "user";
        $params["func"] = "get_account_all_level_info";
        return $this->_call($params);
    }

    public function set_token($token) {
        $this->token = $token;
        $p = $this->getMemcache($token);
        if ($p == true) {
            $block = $p + 1;
            $blocktime = 120;
            if ($block > 5) {
                $blocktime = 600;
            } else if ($block > 10) {
                $blocktime = 3600;
            }
            $this->saveMemcache($token, $block, $blocktime);
            return $block;
        } else {
            $this->saveMemcache($token, 1, 120);
            return 1;
        }
    }

    public function un_token() {
        $this->saveMemcache($this->token, null, 1);
    }

    public function invalid_token($token) {
        $p = $this->getMemcache($token);
        if (empty($token))
            return -1;
        return $this->set_token($token);
    }

    public function writeLogRequest($uri_request, $responseData, $folder, $ip, $group = "request") {

        $CI = &get_instance();
        $CI->config->load("log");
        $config = $CI->config->item("log");
        $config = $config[$group];
        $date = "Y/m";


        if (empty($config) === TRUE)
            die("Empty config log " . $group);

        try {
            $path = $config . "/" . $folder . "/" . date($date);

            if (!file_exists($path))
                @mkdir($path, 0777, TRUE);

            $fp = fopen($path . "/" . date("d") . ".txt", "a");

            $text = date("Y-m-d H:i:s") . "\t" . $uri_request . "\t" . $responseData . "\r\n";

            fwrite($fp, $text);
            fclose($fp);
        } catch (Exception $ex) {

        }
    }

    public function captureRequest(array $arrData, $folder, $ip, $group = "request") {

        $CI = &get_instance();
        $CI->config->load("log");
        $config = $CI->config->item("log");
        $config = $config[$group];
        $date = "Y/m";


        if (empty($config) === TRUE)
            die("Empty config log " . $group);

        try {
            $request = $_SERVER["REQUEST_SCHEME"] . '//' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

            $path = $config . "/" . $folder . "/" . date($date);

            if (!file_exists($path))
                @mkdir($path, 0777, TRUE);

            $fp = fopen($path . "/" . date("d") . ".txt", "a");

            $text = date("Y-m-d H:i:s") . "\t" . $request . "\t" . http_build_query($arrData) . "\r\n";

            fwrite($fp, $text);
            fclose($fp);
        } catch (Exception $ex) {

        }
    }
    public function get_remote_ip() {
        $ipaddress = '';
        if ($_SERVER['HTTP_CLIENT_IP'])
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if ($_SERVER['HTTP_X_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if ($_SERVER['HTTP_X_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if ($_SERVER['HTTP_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if ($_SERVER['HTTP_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if ($_SERVER['REMOTE_ADDR'])
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    public function encrypt($params, $key_seed = "") {
        if (is_array($params)) {
            $input = json_encode($params);
        } else if (is_string($params)) {
            $input = $params;
        } else {
            //throw new Exception('Encrypt data not format.');
            return false;
        }

        $input = trim($input);
        $block = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($input);
        $padding = $block - ($len % $block);
        $input .= str_repeat(chr($padding), $padding);
        // generate a 24 byte key from the md5 of the seed 
        $key = substr(md5($key_seed), 0, 24);
        $iv_size = mcrypt_get_iv_size(MCRYPT_TRIPLEDES, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        // encrypt 
        $encrypted_data = mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $input, MCRYPT_MODE_ECB, $iv);
        // clean up output and return base64 encoded 
        return base64_encode($encrypted_data);
    }

    //end function Encrypt() 

    public function decrypt($input, $key_seed = "") {
        $input = base64_decode($input);
        $key = substr(md5($key_seed), 0, 24);
        $text = mcrypt_decrypt(MCRYPT_TRIPLEDES, $key, $input, MCRYPT_MODE_ECB, '12345678');
        $block = mcrypt_get_block_size('tripledes', 'ecb');
        $packing = ord($text{strlen($text) - 1});
        if ($packing and ( $packing < $block)) {
            for ($P = strlen($text) - 1; $P >= strlen($text) - $packing; $P--) {
                if (ord($text{$P}) != $packing) {
                    $packing = 0;
                }
            }
        }
        $text = substr($text, 0, strlen($text) - $packing);
        $data = json_decode($text, true);
        if (is_array($data)) {
            return $data;
        } else {
            return $text;
        }
    }

    protected function vn_remove($str) {
        $unicode = array(
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        );

        foreach ($unicode as $nonUnicode => $uni) {
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
        }
        return $str;
    }

    protected function _call($params) {
        set_time_limit(120);
        $this->last_link_request = $this->uri_api . "?" . http_build_query($params) . "&app=3k&data=json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->last_link_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $result = curl_exec($ch);
        return $result;
    }

    protected function request($method, $url, $vars) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER["REMOTE_ADDR"]);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->followlocation);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl);
        //curl_setopt($ch, CURLOPT_COOKIEJAR, $this->pathcookie);
        //curl_setopt($ch, CURLOPT_COOKIEFILE, $this->pathcookie);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        }
        $data = curl_exec($ch);
        //var_dump($data);die;
        curl_close($ch);
        if ($data) {
            return $data;
        } else {
            return @curl_error($ch);
        }
    }

}

?>
