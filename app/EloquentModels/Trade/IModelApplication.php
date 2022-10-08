<?php
namespace App\EloquentModels\Trade;

interface IModelApplication
{
    public function __construct();
    public function searchLicence(string $licence_no);
}