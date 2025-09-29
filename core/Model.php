<?php

namespace SGC\Core;

/**
 * Classe de base pour tous les modèles.
 * Fournit une connexion à la base de données.
 */
abstract class Model
{
    protected Database $db;

    /**
     * Le constructeur reçoit la connexion à la base de données.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
}