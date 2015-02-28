<?php

class NetAidManager
{
    static public function setup_ap($ssid, $key)
    {
        if (empty($ssid) || empty($key))
            return false;
        
        $ssid = escapeshellarg($ssid);
        $key  = escapeshellarg($key);
        
        $output = shell_exec("/usr/bin/netaidkit apconfig $ssid $key");
        
        return true;
    }
    
    static public function scan_wifi()
    {
        $output = shell_exec("/usr/bin/netaidkit wifiscan");
        preg_match_all("/ESSID: \"(.*)\"/", $output, $ssids);
        
        return $ssids[1];
    }
    
    static public function setup_wan($ssid, $key)
    {
        if (empty($ssid))
            return false;
            
        $ssid = escapeshellarg($ssid);
        $key  = escapeshellarg($key);
        
        $output = shell_exec("/usr/bin/netaidkit wificonn $ssid $key");
        
        return true;
    }
    
    static public function go_online()
    {
        $output = shell_exec("/usr/bin/netaidkit goonline");
        
        return true;    
    }
    
    static public function set_adminpass($adminpass)
    {
        if (empty($adminpass) || strlen($adminpass) < 8)
            return false;
        
        $passfile = ROOT_DIR . '/data/pass';
        
        $admin_hash = password_hash($adminpass, PASSWORD_BCRYPT);
        
        return file_put_contents($passfile, $admin_hash);
    }
    
    static public function check_adminpass($loginpass)
    {   
        $passfile = ROOT_DIR . '/data/pass';
        
        if (!file_exists($passfile))
            throw new Exception('Password file missing.');

        $admin_hash = file_get_contents($passfile);
        
        return password_verify($loginpass, $admin_hash);
    }    
    
    static public function get_stage()
    {
        return shell_exec("/usr/bin/netaidkit getstage");
    }

    static public function get_inetstat()
    {
        $out = array();
        $status = -1;

        exec("/usr/bin/netaidkit inetstat", $out, $status);
        Log::debug("inetstat result: $status"); // status = 0 when there is inet connection, 4 if there isn't

        if($status == 0) {
            return true;
        } else {
            return false;
        }
    }
    
    static public function set_stage($stage)
    {
        if (empty($stage))
            return false;
            
        $stage = escapeshellarg($stage);
        
        $output = shell_exec("/usr/bin/netaidkit setstage $stage");
        
        return true;
    }
    
    static public function toggle_tor()
    {
        $cur_stage = self::get_stage();
        if ($cur_stage == STAGE_TOR) {
            $mode = 'off';
            self::set_stage(STAGE_ONLINE);
        } elseif ($cur_stage == STAGE_ONLINE) {
            $mode = 'on';
            self::set_stage(STAGE_TOR);
        } else {
            return false;
        }
            
        $output = shell_exec("/usr/bin/netaidkit stagetor $mode");
        
        return true;
    }
    
    static public function toggle_vpn()
    {
        $cur_stage = self::get_stage();
        if ($cur_stage == STAGE_VPN) {
            $mode = 'off';
            self::set_stage(STAGE_ONLINE);
        } elseif ($cur_stage == STAGE_ONLINE) {
            $mode = 'on';
            self::set_stage(STAGE_VPN);
        } else {
            return false;
        }
            
        $output = shell_exec("/usr/bin/netaidkit stagevpn $mode");
        
        return true;
    }
    
    static public function wan_ssid()
    {
        $output = shell_exec("/usr/bin/netaidkit wlaninfo wlan0");

        preg_match_all("/Mode: (Master|Client) /", $output, $mode);
        preg_match_all("/ESSID: \"(.*)\"/", $output, $ssids);

        if ($mode[1][0] == 'Master')
            return 'Wired connection';
        else
            return $ssids[1][0];
    }
}

