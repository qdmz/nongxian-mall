<?php

namespace Core;

/**
 * 基础控制器
 */
class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::instance();
    }

    /** 参数校验失败直接抛异常 */
    protected function validate(array $rules): array
    {
        $data = [];
        foreach ($rules as $field => $rule) {
            $rulesArr = explode('|', $rule);
            $label = $field;
            $value = $this->request->param($field);

            // 解析中文标签 label:xxx
            foreach ($rulesArr as $i => $r) {
                if (str_starts_with($r, 'label:')) {
                    $label = substr($r, 6);
                    unset($rulesArr[$i]);
                }
            }

            foreach ($rulesArr as $r) {
                [$name, $param] = array_pad(explode(':', $r, 2), 2, null);
                switch ($name) {
                    case 'required':
                        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                            json_error("{$label}不能为空");
                        }
                        break;
                    case 'int':
                        if ($value !== null && !is_numeric($value)) {
                            json_error("{$label}必须是数字");
                        }
                        if ($value !== null) $value = (int)$value;
                        break;
                    case 'float':
                        if ($value !== null && !is_numeric($value)) {
                            json_error("{$label}必须是数字");
                        }
                        if ($value !== null) $value = (float)$value;
                        break;
                    case 'min':
                        if (is_string($value) && mb_strlen($value) < (int)$param) {
                            json_error("{$label}最少{$param}个字符");
                        }
                        if (is_numeric($value) && $value < (float)$param) {
                            json_error("{$label}不能小于{$param}");
                        }
                        break;
                    case 'max':
                        if (is_string($value) && mb_strlen($value) > (int)$param) {
                            json_error("{$label}最多{$param}个字符");
                        }
                        if (is_numeric($value) && $value > (float)$param) {
                            json_error("{$label}不能大于{$param}");
                        }
                        break;
                    case 'phone':
                        if ($value && !preg_match('/^1[3-9]\d{9}$/', (string)$value)) {
                            json_error("{$label}格式不正确");
                        }
                        break;
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            json_error("{$label}格式不正确");
                        }
                        break;
                    case 'in':
                        $options = explode(',', (string)$param);
                        if ($value !== null && $value !== '' && !in_array((string)$value, $options, true)) {
                            json_error("{$label}取值不合法");
                        }
                        break;
                    case 'array':
                        if ($value !== null && !is_array($value)) {
                            json_error("{$label}必须是数组");
                        }
                        break;
                }
            }
            if ($value !== null) {
                $data[$field] = $value;
            }
        }
        return $data;
    }

    /** 分页参数 */
    protected function pageParams(int $maxPageSize = 100): array
    {
        $page = max(1, $this->request->int('page', 1));
        $pageSize = $this->request->int('page_size', 15);
        $pageSize = min(max(1, $pageSize), $maxPageSize);
        return [$page, $pageSize, ($page - 1) * $pageSize];
    }
}
