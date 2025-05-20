<?php

namespace App\Traits;

trait ApiResponse
{

    /**
     * [
     *  code    => default 200 and dataType is int.
     *  message => default null and dataType is String.
     *  errors  => default null and dataType is Array.
     *  data    => default null and dataType id Array
     * ]
     */
    public function apiResponse($code = 200, $message = null, $errors = null, $data = null)
    {
        $array = [
            'status'  => $code,
            'message' => $message,
        ];

        if (is_null($data) && !is_null($errors)) {
            $array['errors'] = $errors;
        } elseif (is_null($errors) && !is_null($data)) {
            $array['data'] = $data;
        } elseif(!is_null($data) && !is_null($errors)) {
            $array['data'] = $data;
            $array['errors'] = $errors;
        }

        return response($array, $code);
    }

}
