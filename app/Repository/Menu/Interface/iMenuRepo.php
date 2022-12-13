<?php

namespace App\Repository\Menu\Interface;

/**
 * | Created On-23-11-2022 
 * | Created By-Anshu Kumar
 * | Updated By-Sam kerketta
 * | Interface for the Menu Permission 
 */

interface iMenuRepo
{
    public function getAllMenues();             // Get All menu List
    public function getMenuByRoles($req);       // Get All the menu by roles
    public function updateMenuByRole($req);     // Enable or Disable Menu by Role
    public function getRoleWiseMenu();          // get userRole eise menues
    public function generateMenuTree($req);     // Generate a Menu Tree Structure
}
