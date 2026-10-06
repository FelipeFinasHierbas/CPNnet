<?php
file_put_contents(__DIR__.'/hook.log', json_encode(['headers'=>array_change_key_case(getallheaders()),'body'=>file_get_contents('php://input')])."\n", FILE_APPEND); http_response_code(200); echo 'ok';
