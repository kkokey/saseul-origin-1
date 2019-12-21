<?php

namespace Saseul\Common;

use Saseul\Constant\HttpStatus;

class Api
{
    public $code = HttpStatus::OK;
    public $data = [];
    protected $display_params = true;
    protected $result = [];

    public function main() {}

    public function exec(): void
    {
        $this->main();
        $this->success();
    }

    protected function success()
    {
        $this->result['status'] = 'success';
        $this->result['data'] = $this->data;

        if ($this->display_params === true) {
            $this->result['params'] = $_REQUEST;
        }

        $this->view();
    }

    protected function fail($code, $msg = '')
    {
        $this->result['status'] = 'fail';
        $this->result['code'] = $code;
        $this->result['msg'] = $msg;
        $this->code = $code;
        $this->view();
    }

    protected function error(string $msg = 'Error', $code = 999)
    {
        $this->fail($code, $msg);
    }

    protected function view()
    {
        try {
            header('Content-Type: application/json; charset=utf-8;');
        } catch (\Exception $e) {
            echo $e . PHP_EOL . PHP_EOL;
        }

        http_response_code($this->code);
        echo json_encode($this->result);
        exit();
    }

    /**
     * Get request parameter.
     * If not set request parameter, default parameter data is set.
     *
     * @param array $request
     * @param string $key
     * @param array $options keys [default, type]
     *
     * @return float|int|string
     */
    protected function getParam($request, string $key, array $options = [])
    {
        if (!isset($request[$key]) && !isset($options['default'])) {
            $this->Error("There is no parameter: {$key}");
        }

        $param = $request[$key] ?? $options['default'];

        if (isset($options['type']) && !static::checkType($param, $options['type'])) {
            $this->Error("Wrong parameter type: {$key}");
        }

        return $param;
    }

    /**
     * Use $type to check the type of $param data.
     *
     * @param        $param
     * @param string $type
     *
     * @return bool
     */
    public static function checkType($param, string $type): bool
    {
        if (($type === 'string') && !is_string($param)) {
            return false;
        }

        if (($type === 'numeric') && !is_numeric($param)) {
            return false;
        }

        return true;
    }
}