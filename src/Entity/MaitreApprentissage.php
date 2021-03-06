<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MaitreApprentissage
 *
 * @ORM\Table(name="maitre_apprentissage", options={"comment":"Les informations des maîtres d'appentissages"}, indexes={@ORM\Index(name="IDX_FC0B595DA8937AB7", columns={"id_entreprise"})})
 * @ORM\Entity(repositoryClass="App\Repository\MaitreApprentissageRepository")
 */
class MaitreApprentissage
{
    /**
     * @var string
     *
     * @ORM\Column(name="fonction", type="string", length=256, nullable=true)
     * @Assert\NotBlank(message="La fonction ne peut pas être vide.", groups={"profil"})
     */
    private $fonction;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=10, nullable=true)
     * @Assert\Length(min = 10, max = 10,
     *     exactMessage = "Le numéro de téléphone doit faire {{ limit }} caractères.", groups={"profil"})
     * @Assert\NotBlank(message="Le numéro de téléphone ne peut pas être vide.", groups={"profil"})
     * @Assert\Type(
     *     type="numeric",
     *     message="Le téléphone ne doit contenir que des chiffres.", groups={"profil"}
     * )
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=10, nullable=true)
     */
    private $fax;

    /**
     * @var Entreprise
     *
     * @ORM\ManyToOne(targetEntity="Entreprise")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_entreprise", referencedColumnName="id")
     * })
     * @Assert\Valid()
     */
    private $entreprise;

    /**
     * @var User
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_compte", referencedColumnName="id")
     * })
     * @Assert\Valid(groups={"ajout"})
     */
    private $compte;


    /**
     * @return string
     */
    public function getFonction()
    {
        return $this->fonction;
    }

    /**
     * @param string $fonction
     */
    public function setFonction(string $fonction): void
    {
        $this->fonction = $fonction;
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     */
    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @param string $fax
     */
    public function setFax(string $fax): void
    {
        $this->fax = $fax;
    }

    /**
     * @return Entreprise
     */
    public function getEntreprise(): Entreprise
    {
        return $this->entreprise;
    }

    /**
     * @param Entreprise $entreprise
     */
    public function setEntreprise(Entreprise $entreprise): void
    {
        $this->entreprise = $entreprise;
    }

    /**
     * @return User
     */
    public function getCompte(): User
    {
        return $this->compte;
    }

    /**
     * @param User $compte
     */
    public function setCompte(User $compte): void
    {
        $this->compte = $compte;
    }

}

