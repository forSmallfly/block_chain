<?php
// +----------------------------------------------------------------------
// | 合约方法配置
// +----------------------------------------------------------------------
return [
    'ERC20'        => [
        'totalSupply'  => [
            'method' => 'totalSupply()',
            'params' => []
        ],
        'balanceOf'    => [
            'method' => 'balanceOf(address)',
            'params' => ['address']
        ],
        'transfer'     => [
            'method' => 'transfer(address,uint256)',
            'params' => ['address', 'uint256']
        ],
        'transferFrom' => [
            'method' => 'transferFrom(address,address,uint256)',
            'params' => ['address', 'address', 'uint256']
        ],
        'approve'      => [
            'method' => 'approve(address,uint256)',
            'params' => ['address', 'uint256']
        ],
        'allowance'    => [
            'method' => 'allowance(address,address)',
            'params' => ['address', 'address']
        ],
        'getFee'       => [
            'method' => 'getFee(uint256)',
            'params' => ['uint256']
        ],
    ],
    'ERC721'       => [
        'ownerOf'             => [
            'method'  => 'ownerOf(uint256)',
            'params'  => ['uint256'],
            'outputs' => ['address']
        ],
        'balanceOf'           => [
            'method'  => 'balanceOf(address)',
            'params'  => ['address'],
            'outputs' => ['uint256']
        ],
        'tokenOfOwnerByIndex' => [
            'method'  => 'tokenOfOwnerByIndex(address,uint256)',
            'params'  => ['address', 'uint256'],
            'outputs' => ['uint256']
        ],
    ],
    'CUSTOM_ERC20' => [
        'mint' => [
            'method'  => 'mint(address,uint256)',
            'params'  => ['address', 'uint256'],
            'outputs' => ['address', 'address', 'uint256', 'uint256']
        ]
    ]
];