<?php

return [
    'preset'                 => 'laravel',
    'rules'                  => [
        'binary_operator_spaces'            => false,
        'single_blank_line_at_eof'          => false,
        'concat_space'                      => false,
        'type_declaration_spaces'           => false,
        'braces_position'                   => false,
        'cast_spaces'                       => false,
        'not_operator_with_successor_space' => false,
    ],
    'binary_operator_spaces' => [
        'default'   => 'single_space',
        'operators' => [
            '=' => 'align_single_space',
        ],
    ],
];
