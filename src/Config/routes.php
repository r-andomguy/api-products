<?php

use Contatoseguro\TesteBackend\Controller\CategoryController;
use Contatoseguro\TesteBackend\Controller\CompanyController;
use Contatoseguro\TesteBackend\Controller\HomeController;
use Contatoseguro\TesteBackend\Controller\ProductController;
use Contatoseguro\TesteBackend\Controller\ReportController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @var App $app*/
$app->get('/', [HomeController::class, 'home']);

$app->group('/companies', function (RouteCollectorProxy $group) {
    $group->get('', [CompanyController::class, 'getAll']);
    $group->get('/{id}', [CompanyController::class, 'getOne']);
});

$app->group('/products', function (RouteCollectorProxy $group) {
    $group->get('', [ProductController::class, 'getAll']);
    $group->get('/{id}', [ProductController::class, 'getOne']);
    $group->get('/last-edit/{id}', [ProductController::class, 'getLastLog']);
    $group->get('/{id}/comments', [ProductController::class, 'getComments']);
    $group->post('', [ProductController::class, 'insertOne']);
    $group->post('/{id}/comment/{id_comment}/like', [ProductController::class, 'insertCommentLike']);
    $group->post('/{id}/comment', [ProductController::class, 'insertComment']);
    $group->put('/{id}', [ProductController::class, 'updateOne']);
    $group->put('/stock/{id}', [ProductController::class, 'updateProductStock']);
    $group->delete('/{id}', [ProductController::class, 'deleteOne']);
    $group->delete('/{id}/comment/{id_comment}', [ProductController::class, 'deleteComment']);
});

$app->group('/categories', function (RouteCollectorProxy $group) {
    $group->get('', [CategoryController::class, 'getAll']);
    $group->get('/{id}', [CategoryController::class, 'getOne']);
    $group->post('', [CategoryController::class, 'insertOne']);
    $group->post('/{id}', [CategoryController::class, 'insertTranslations']);
    $group->put('/{id}', [CategoryController::class, 'updateOne']);
    $group->delete('/{id}', [CategoryController::class, 'deleteOne']);
});

$app->get('/report', [ReportController::class, 'generate']);
