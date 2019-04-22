<?php

namespace lib;

use lib;

class helper
{
    public function getParam($key, $default = '', $filter = 'trim')
    {
        if (isset($_POST[$key])) {
            $param = $_POST[$key];
        } elseif (isset($_GET[$key])) {
            $param = $_GET[$key];
        } else {
            $param = $default;
        }
        if (is_string($param)) {
            $param = trim($param);
        }
        switch ($filter) {
            case 'trim':
                return trim($param);
                break;
            case 'int':
                return abs(intval($param));
                break;
            case 'numeric':
                return (float)$param;
                break;
            case 'string':
                return lib\ku\tool::filter($param);
            default:
                # code...
                break;
        }
        return $param;
    }

    public function getPost()
    {
        $post = $_POST;
        if (!empty($post)) {
            foreach ($post as $key => $value) {
                $post[$key] = lib\ku\tool::filter($value);
            }
        }
        return $post;
    }

    public function getGet()
    {
        $gets = $_GET;
        if (!empty($gets)) {
            foreach ($gets as $key => $value) {
                $gets[$key] = lib\ku\tool::filter($value);
            }
        }
        return $gets;
    }

    public function getAllParams()
    {
        $gets = $_GET;
        if (!empty($gets)) {
            foreach ($gets as $key => $value) {
                $gets[$key] = lib\ku\tool::filter($value);
            }
        }
        $posts = $_POST;
        if (!empty($posts)) {
            foreach ($posts as $key => $value) {
                $posts[$key] = lib\ku\tool::filter($value);
            }
        }
        $requests = $_REQUEST;
        if (!empty($requests)) {
            foreach ($requests as $key => $value) {
                $requests[$key] = lib\ku\tool::filter($value);
            }
        }
        $params = array_merge($requests,$gets,$posts);
        return $params;
    }

}

