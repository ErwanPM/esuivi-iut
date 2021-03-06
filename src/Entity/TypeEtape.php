<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TypeEtape
 *
 * @ORM\Table(name="type_etape", options={"comment":"Les types d'étapes d'un dossier"})
 * @ORM\Entity(repositoryClass="App\Repository\TypeEtapeRepository")
 */
class TypeEtape
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="type_etape_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_etape", type="string", length=100, nullable=false)
     */
    private $nomEtape;

    /**
     * @var string
     *
     * @ORM\Column(name="type_validateur", type="string", length=32, nullable=false)
     */
    private $typeValidateur;

    /**
     * @var string
     *
     * @ORM\Column(name="type_icone", type="string", length=100, nullable=true)
     */
    private $typeIcone;

    /**
     * @var integer
     *
     * @ORM\Column(name="position_etape", type="integer", nullable=true, unique=true)
     */
    private $positionEtape;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getNomEtape(): string
    {
        return $this->nomEtape;
    }

    /**
     * @param string $nomEtape
     */
    public function setNomEtape(string $nomEtape): void
    {
        $this->nomEtape = $nomEtape;
    }

    /**
     * @return string
     */
    public function getTypeValidateur(): string
    {
        return $this->typeValidateur;
    }

    /**
     * @param string $typeValidateur
     */
    public function setTypeValidateur(string $typeValidateur): void
    {
        $this->typeValidateur = $typeValidateur;
    }

    /**
     * @return string
     */
    public function getTypeIcone(): string
    {
        return $this->typeIcone;
    }

    /**
     * @param string $typeIcone
     */
    public function setTypeIcone(string $typeIcone): void
    {
        $this->typeIcone = $typeIcone;
    }

    /**
     * @return integer
     */
    public function getPositionEtape(): int
    {
        return $this->positionEtape;
    }

    /**
     * @param integer $positionEtape
     */
    public function setPositionEtape(int $positionEtape): void
    {
        $this->positionEtape = $positionEtape;
    }

}

