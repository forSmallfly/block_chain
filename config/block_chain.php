<?php
// +----------------------------------------------------------------------
// | 区块链配置
// +----------------------------------------------------------------------
return [
    // 部署链标识
    'chain_id'                    => env('block_chain.chain_id', ''),
    // 区块链列表
    'chain_list'                  => [
        // 火币正式链
        128 => [
            'chain_id'  => 128,
            'name'      => 'HECO',
            'base_coin' => 'HT',
            'rpc'       => 'https://http-mainnet.hecochain.com',
            'scan_url'  => 'https://hecoinfo.com',
            'official'  => true
        ],
        // 火币测试链
        256 => [
            'chain_id'  => 256,
            'name'      => 'HECO',
            'base_coin' => 'HT',
            'rpc'       => 'https://http-testnet.hecochain.com',
            'scan_url'  => 'https://testnet.hecoinfo.com',
            'official'  => false
        ],
        // 币安正式链
        56  => [
            'chain_id'  => 56,
            'name'      => 'BSC',
            'base_coin' => 'BNB',
            'rpc'       => 'https://bsc-dataseed1.binance.org',
            'scan_url'  => 'https://bscscan.com',
            'official'  => true
        ],
        // 币安测试链
        97  => [
            'chain_id'  => 97,
            'name'      => 'BSC',
            'base_coin' => 'BNB',
            'rpc'       => 'https://data-seed-prebsc-1-s1.binance.org:8545',
            'scan_url'  => 'https://testnet.bscscan.com',
            'official'  => false
        ],
    ],
    // 额外的gas费率，例：1.1表示多支付10%的gas费（发交易正常算出的gas费一般偏少，会导致交易无法成功）
    'gas_ratio'                   => env('block_chain.gas_ratio', 0),
    // 区块获取失败重试次数
    'listen_block_try_times'      => 3,
    // 交易获取失败重试次数
    'listen_receipt_try_times'    => 3,
    // 重试睡眠间隔，单位秒，可传小数，需要大于等于0.001，否则会报错
    'listen_block_try_sleep_time' => '0.1,0.3,0.5',
    // 脚本处理最大块数
    'listen_block_number_max'     => 1000,
    // 并发处理区块数量（切割数量）
    'listen_block_number'         => 100,
    // 并发处理 间隔睡眠时间--微秒
    'listen_block_sleep'          => 500000,
    // 自动交易处理失败重发次数
    'retry_count'                 => 3,
    // 自动交易处理失败重发时间间隔,间隔数必须与重发数一致！！！（单位：秒）
    'retry_times'                 => '10,20,30',

    // 需要监听的合约地址集合（合约地址 => 监听文件）
    'need_listen_contract_list'   => [
        '0x66a6487ac7bc1bc4ae7e8da57b2ef636f98aaddf' => \monitor\service\Template::class,
    ],
    // 需要监听的钱包地址集合（合约地址 => 监听文件）
    'need_listen_wallet_list'     => [
        '0xac05d75850dfed2d94a940fcb60b038818ad9a7e' => \monitor\service\Collect::class,
    ],

    // neo领取nft多账号钱包地址集合（钱包地址 => 钱包秘钥）
    'token_minter_role_list'      => [
        '0xAE02B5f81B07Cd42Db13Ac8d0B7092eaaa5D33DE' => 'f1e1fdb527678238e3111fc088d78cf98426b76d26bec1693c9a066aff2388bd',
        '0x513757670Fe16E086982f7e292124CEe34f47eF0' => '2153aaf5415d388306327df7f943ff4d8ac1fefe31ac2901c95952b1006016a3',
    ]
];