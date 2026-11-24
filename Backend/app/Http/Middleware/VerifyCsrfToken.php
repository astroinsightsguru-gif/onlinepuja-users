<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'admin/loginApi',
        'loginApi',
        'editAstrolgerCategoryApi',
        'astrologyCategoryStatusApi',
        'verifiedAstrologerApi',
        'addAstrolgerCategoryApi',
        'editBlogApi',
        'addBlogApi',
        'deleteUser',
        'editProductApi',
        'addProductDetailApi',
        'releaseAmount',
        'admin/ask-master',
        'admin/ask-chatgpt'
    ];
}
