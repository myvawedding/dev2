<?php
if (isset($error)) {
    echo $this->JsonEncode(array('error' => $this->H($error)));
    return;
}
$file_arr = array('success' => 1);
foreach ($files as $file) {
    $file['icon'] = $this->FileIcon($file['extension']);
    $file_arr['files'][] = array_map(array($this, 'H'), $file);
}
echo $this->JsonEncode($file_arr);