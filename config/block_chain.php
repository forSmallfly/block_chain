<?php
// +----------------------------------------------------------------------
// | 区块链配置
// +----------------------------------------------------------------------
return [
    // 项目名称（不同项目会使用项目名作为redis前缀）
    'project_name' => 'block_chain',
    // 部署链标识
    'chain_id'     => env('block_chain.chain_id', ''),
    // 区块链列表
    'chain_list'   => [
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
    'gas_ratio'    => env('block_chain.gas_ratio', 0),
];