<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Rposul_Exporter_PyExporterComm {

    private static function request_data($body) {
        $key = "mykey";
        //$key = "8feeb34c-fd7a-4d43-bbe0-ad7dc2b65143";
        if (USE_DEBUG_SERVER_VERSION) {
            $body['debug'] = true;
            $api_path = RPOSUL_SERVICE_URL . "debug/pyexporter.php?key=$key";
        } else {
            $api_path = RPOSUL_SERVICE_URL . "pyexporter.php?key=$key";
        }
        $body['encoding'] = mb_internal_encoding();        
        
        $response = wp_remote_post($api_path, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => $body,
            'cookies' => array()));
        
        if (is_wp_error($response)) {
            return $response;
        } else {
            $pampa_server_response = json_decode($response['body'], true);
            if ($pampa_server_response['server_status'] == 0) {
                return $pampa_server_response['server_data'];
            } else {
                return new WP_Error('server', $pampa_server_response['server_message']);
            }
        }
    }

    public static function get_log() {
        if (DISABLE_COMM) {
            return "No log available";
        }

        $response = self::request_data(array('function' => 'log'));
        if (!is_wp_error($response)) {
            return $response;
        } else {
            return "No log available";
        }
    }

    public static function get_state() {
        if (DISABLE_COMM) {
            return array('done' => false, 'running' => false, 'totalPages' => 10, 'currentPage' => 2);
        }

        $response = self::request_data(array('function' => 'status'));
        if (!is_wp_error($response)) {
            return $response;
        } else {
            return false;
        }
    }

    public static function clear_cache() {
        if (DISABLE_COMM) {
            return true;
        }
        
        return self::request_data(array('function' => 'new'));
    }

    public static function cancel_execution() {
        if (DISABLE_COMM) {
            return true;
        }

        return self::request_data(array('function' => 'cancel'));
    }
    
    public static function publish() {
        if (DISABLE_COMM) {
            return true;
        }

        return self::request_data(array('function' => 'publish'));
    }

    public static function add_page($filename, $tex_content, $json_content) {
        if (DISABLE_COMM) {
            return true;
        }

        return self::request_data(array(
                    'function' => 'add',
                    'filename' => $filename,
                    'tex' => $tex_content,
                    'json' => $json_content));
    }

    public static function generate(
            $news_date, 
            $generate_date, 
            $quick_run, 
            $page=0) {
                
        $request_data = array(
                    'function' => 'generate',
                    'news_date' => $news_date,
                    'generate_date' => $generate_date,
                    'quick_run' => $quick_run);
        
        if ($page>0){
            $request_data['page'] = $page;
            $request_data['merge'] = true;
        }
        
        if (USE_PAMPA_SERVER) {
            $request_data['multiprocess'] = true;
        }
        
        if (DISABLE_COMM) {
            return true;
        }
        if (DISABLE_GENERATE) {
            return true;
        }
        
        return self::request_data($request_data);
    }

}
