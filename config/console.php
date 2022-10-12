<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        // 区块同步
        'block_sync'      => \app\command\BlockSync::class,
        // token铸造
        'auto_token_mint' => \app\command\AutoTokenMint::class
    ],
];
