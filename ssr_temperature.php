<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die('<br><strong>This script is only meant to run at the command line.</strong>');
}

global $config;

$no_http_headers = true;

/* display No errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
        include_once(dirname(__FILE__) . '/../include/global.php');
        include_once(dirname(__FILE__) . '/../lib/snmp.php');

        array_shift($_SERVER['argv']);

        print call_user_func_array('ssr_temperature', $_SERVER['argv']);
} else {
        include_once($config['library_path'] . '/snmp.php');
}


function ssr_temperature($hostname, $host_id, $snmp_auth, $cmd, $arg1 = '', $arg2 = '')
{
    # |host_hostname| |host_id| |host_snmp_version|:|host_snmp_port|:|host_snmp_timeout|:
    # |host_ping_retries|:|host_max_oids|:|host_snmp_community|:|host_snmp_username|:
    # |host_snmp_password|:|host_snmp_auth_protocol|:|host_snmp_priv_passphrase|:
    # |host_snmp_priv_protocol|:|host_snmp_context|

    $snmp         = explode(':', $snmp_auth);
    $host_args = array();

    $host_args['version']         = $snmp[0];
    $host_args['port']            = $snmp[1];
    $host_args['timeout']         = $snmp[2];
    $host_args['ping_retries']    = $snmp[3];
    $host_args['max_oids']        = $snmp[4];

    $host_args['auth_username']   = '';
    $host_args['auth_password']   = '';
    $host_args['auth_protocol']   = '';
    $host_args['priv_passphrase'] = '';
    $host_args['priv_protocol']   = '';
    $host_args['context']         = '';
    $host_args['community']       = '';

    if ($host_args['version'] === '3') {
        $host_args['auth_username']   = $snmp[6];
        $host_args['auth_password']   = $snmp[7];
        $host_args['auth_protocol']   = $snmp[8];
        $host_args['priv_passphrase'] = $snmp[9];
        $host_args['priv_protocol']   = $snmp[10];
        $host_args['context']         = $snmp[11];
    } else {
        $host_args['community']       = $snmp[5];
    }

    $host_args['oids'] = array(
        "rbnEntityTempDescr" => ".1.3.6.1.4.1.2352.2.4.1.6.1.2",
        "rbnEntityTempCurrent"   => ".1.3.6.1.4.1.2352.2.4.1.6.1.3"
            );

    if (($cmd == 'index')) {
        $arr_index = ssr_temperature_reindex(cacti_snmp_walk(
            $hostname,
            $host_args['community'],
            $host_args['oids']['rbnEntityTempDescr'],
            $host_args['version'],
            $host_args['auth_username'],
            $host_args['auth_password'],
            $host_args['auth_protocol'],
            $host_args['priv_passphrase'],
            $host_args['priv_protocol'],
            $host_args['context'],
            $host_args['port'],
            $host_args['timeout'],
            $host_args['ping_retries'],
            $host_args['max_oids'],
            SNMP_POLLER
        ));
	//$arr_index;
        foreach ($arr_index as $index => $value) {
            print $index . "\n";
        }
    } elseif (($cmd == 'num_indexes')) {
        $arr_index = ssr_temperature_reindex(cacti_snmp_walk(
            $hostname,
            $host_args['community'],
            $host_args['oids']['rbnEntityTempDescr'],
            $host_args['version'],
            $host_args['auth_username'],
            $host_args['auth_password'],
            $host_args['auth_protocol'],
            $host_args['priv_passphrase'],
            $host_args['priv_protocol'],
            $host_args['context'],
            $host_args['port'],
            $host_args['timeout'],
            $host_args['ping_retries'],
            $host_args['max_oids'],
            SNMP_POLLER
        ));
        return sizeof($arr_index);
    } elseif ($cmd == 'query') {
        switch ($arg1) {
            case "rbnEntityTempDescr":
                $arr = ssr_temperature_desc($hostname, $host_args);
                break;

            case "rbnEntityTempCurrent":
                $arr = ssr_temperature_reindex(cacti_snmp_walk(
                    $hostname,
                    $host_args['community'],
                    $host_args['oids'][$arg1],
                    $host_args['version'],
                    $host_args['auth_username'],
                    $host_args['auth_password'],
                    $host_args['auth_protocol'],
                    $host_args['priv_passphrase'],
                    $host_args['priv_protocol'],
                    $host_args['context'],
                    $host_args['port'],
                    $host_args['timeout'],
                    $host_args['ping_retries'],
                    $host_args['max_oids'],
                    SNMP_POLLER
                ));
                break;
        }

        foreach ($arr as $index => $value) {
            print $index.':'.$value."\n";
        }
    } elseif ($cmd == 'get') {

        $index = rtrim($arg2);

        switch ($arg1) {


            case "rbnEntityTempDescr":
                $arr = ssr_temperature_desc($hostname, $host_args);
                if (isset($arr[$index])) {
                    return $arr[$index];
                } else {
                    cacti_log('ERROR: Invalid Return Value in ssr_temperature.php for get ('.$arg1.') '.$index.' and host_id '.$host_id, false);
                    return 'U';
                }
                break;

            case "rbnEntityTempCurrent":
                $value = cacti_snmp_get(
                    $hostname,
                    $host_args['community'],
                    $host_args['oids'][$arg1].'.'.$index,
                    $host_args['version'],
                    $host_args['auth_username'],
                    $host_args['auth_password'],
                    $host_args['auth_protocol'],
                    $host_args['priv_passphrase'],
                    $host_args['priv_protocol'],
                    $host_args['context'],
                    $host_args['port'],
                    $host_args['timeout'],
                    $host_args['ping_retries'],
                    $host_args['max_oids'],
                    SNMP_POLLER
                );
		//print_r($value);
	        if(preg_match("/^[1-9][0-9]/",$value)) {
                    cacti_log('ERROR: Invalid Return Value ['. $value .'] in ssr_temperature.php for get ('.$arg1.'), Index: '.$index.' and host_id '.$host_id, false);
                }
                return $value;
                break;
        }

        cacti_log('ERROR: Unable to determine get type in ssr_temperature.php for get ('.$arg1.') '.$index.' and host_id '.$host_id, false);
        return 'U';
    }
}

function ssr_temperature_desc($hostname, $host_args)
{
    $arr = ssr_temperature_reindex(cacti_snmp_walk(
        $hostname,
        $host_args['community'],
        $host_args['oids']['rbnEntityTempDescr'],
        $host_args['version'],
        $host_args['auth_username'],
        $host_args['auth_password'],
        $host_args['auth_protocol'],
        $host_args['priv_passphrase'],
        $host_args['priv_protocol'],
        $host_args['context'],
        $host_args['port'],
        $host_args['timeout'],
        $host_args['ping_retries'],
        $host_args['max_oids'],
        SNMP_POLLER
    ));
    $return_arr = array();

    foreach ($arr as $index => $value) {
        if (is_string($index)) {
            if (isset($arr[$index])) {
		list($temp,$tmp) = explode("Temperature ",$value);
		list($descr,$temp) = explode(" state Normal",$tmp);
		$return_arr[$index] = $descr;
	 }
        }
    }

    return $return_arr;
}

function ssr_temperature_reindex($arr)
{
    $return_arr = array();
    for ($i=0; ($i<sizeof($arr)); $i++) {
     if(preg_match("/temperature/",$arr[$i]['value']) or preg_match("/Temperature/",$arr[$i]['value']) )
     {
	list($temp,$index) = explode(".1.3.6.1.4.1.2352.2.4.1.6.1.2.",$arr[$i]['oid']);
        if (is_string($index)) {
            $return_arr[$index] = $arr[$i]['value'];
        }
     }
     if(preg_match("/^[1-9][0-9]/",$arr[$i]['value']))
     {
      list($temp,$index) = explode(".1.3.6.1.4.1.2352.2.4.1.6.1.3.",$arr[$i]['oid']);
      $return_arr[$index] = $arr[$i]['value'];
     }
    }
    return $return_arr;
}
