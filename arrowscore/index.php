<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'core/Router.php';
require_once 'core/Auth.php';

$router = new Router();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Publik
$router->addRoute('', 'LiveScoreController@index');
$router->addRoute('live-score/{slug}', 'LiveScoreController@show');
$router->addRoute('series-standings/{slug}', 'LiveScoreController@seriesStandings');
$router->addRoute('alokasi/{round_id}', 'LiveScoreController@viewAllocation');

// Input skor
$router->addRoute('input/{slug}', 'InputController@login');
$router->addRoute('input/{slug}/grid', 'InputController@grid');
$router->addRoute('input/{slug}/save', 'InputController@save');
$router->addRoute('input/{slug}/submit', 'InputController@submit');
$router->addRoute('input/{slug}/heartbeat', 'InputController@heartbeat');

// Admin
$router->addRoute('admin', 'AdminController@login');
$router->addRoute('admin/dashboard', 'AdminController@dashboard');
$router->addRoute('admin/competitions', 'AdminController@competitions');
$router->addRoute('admin/rounds/{id}', 'AdminController@rounds');
$router->addRoute('admin/generate-links/{round_id}', 'AdminController@generateLinks');
$router->addRoute('admin/correction/{round_id}', 'AdminController@correction');
$router->addRoute('admin/logout', 'AdminController@logout');
$router->addRoute('admin/finish-round/{round_id}', 'AdminController@finishRound');
$router->addRoute('admin/series-points/{competition_id}', 'AdminController@seriesPoints');
$router->addRoute('admin/series-points/{competition_id}/save', 'AdminController@saveSeriesPoints');
$router->addRoute('admin/edit-round/{round_id}', 'AdminController@editRound');
$router->addRoute('admin/delete-round/{round_id}', 'AdminController@deleteRound');
$router->addRoute('admin/add-session-targets/{round_id}', 'AdminController@addSessionTargets');
$router->addRoute('admin/delete-link/{session_id}', 'AdminController@deleteLink');
$router->addRoute('admin/edit-category/{category_id}', 'AdminController@editCategory');
$router->addRoute('admin/delete-category/{category_id}', 'AdminController@deleteCategory');
$router->addRoute('admin/unlock-link/{session_id}', 'AdminController@unlockLink');
$router->addRoute('admin/open-round/{round_id}', 'AdminController@openRound');
$router->addRoute('admin/correction/{round_id}/pdf', 'AdminController@correctionPdf');

// Peserta (kompetisi)
$router->addRoute('admin/participants/{competition_id}', 'ParticipantController@index');
$router->addRoute('admin/participants/{competition_id}/add', 'ParticipantController@add');
$router->addRoute('admin/participants/{competition_id}/import', 'ParticipantController@import');
$router->addRoute('admin/participants/{competition_id}/delete/{participant_id}', 'ParticipantController@deleteFromCompetition');
$router->addRoute('admin/participants/{competition_id}/delete-batch', 'ParticipantController@deleteBatchFromCompetition');
$router->addRoute('admin/participants/edit/{participant_id}', 'ParticipantController@editParticipant');

// Peserta per Kategori
$router->addRoute('cp/{category_id}', 'CategoryParticipantController@index');
$router->addRoute('cp/{category_id}/add', 'CategoryParticipantController@add');
$router->addRoute('cp/{category_id}/import', 'CategoryParticipantController@import');
$router->addRoute('cp/{category_id}/edit/{participant_id}', 'CategoryParticipantController@edit');
$router->addRoute('cp/{category_id}/delete/{participant_id}', 'CategoryParticipantController@delete');
$router->addRoute('cp/{category_id}/delete-batch', 'CategoryParticipantController@deleteBatch');
$router->addRoute('cp/{category_id}/download-template', 'CategoryParticipantController@downloadTemplate');

// Alokasi Face Target
$router->addRoute('admin/assign-targets/{round_id}', 'ParticipantController@assignTargets');
$router->addRoute('admin/assign-targets/{round_id}/save', 'ParticipantController@saveAssignments');

// Peserta per Babak (round)
$router->addRoute('admin/round-participants/{round_id}', 'CategoryParticipantController@roundParticipants');
$router->addRoute('admin/round-participants/{round_id}/add', 'CategoryParticipantController@addRoundParticipant');
$router->addRoute('admin/round-participants/{round_id}/delete/{participant_id}', 'CategoryParticipantController@deleteRoundParticipant');
$router->addRoute('admin/round-participants/{round_id}/delete-batch', 'CategoryParticipantController@deleteBatchRoundParticipants');
$router->addRoute('admin/round-participants/{round_id}/import', 'CategoryParticipantController@importRoundParticipants');

$router->run();