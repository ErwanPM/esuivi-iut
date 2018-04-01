<?php

namespace App\Controller;

use App\Entity\CorrespondantEntreprise;
use App\Entity\MaitreApprentissage;
use App\Form\CompteType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Annotation\Route; //To define the route to access it
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Form\EntrepriseType;
use App\Form\MaitreApprentissageType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntrepriseController extends Controller
{
    /**
     * @Route("/entreprise/informations/", name="infos_entreprise")
     */
    public function infos_entreprise(Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder)
    {
        $entreprises = $this->getDoctrine()->getRepository(Entreprise::class)->findAll();

        $ma = new MaitreApprentissage();
        $user = new User();
        $ma->setCompte($user);
        $entreprise = new Entreprise();
        $ma->setEntreprise($entreprise);
        $form = $this->createForm(MaitreApprentissageType::class, $ma);

        $selectionEntreprise = null;
        $selectionMaitre = null;
        $maitres = null;
        $error = false;
        $addMa = false;

        if ($request->isMethod('POST')) {

            $form->handleRequest($request);

            $em = $this->getDoctrine()->getManager();

            $selectionEntreprise = $request->request->get('select_entreprise');
            $selectionMaitre = $request->request->get('select_maitre');

            if (!empty($selectionEntreprise && !empty($selectionMaitre)) /*&& !empty($selectionMaitre)*/) {
                if ($form->isSubmitted()) {

                    //Choix autre entreprise
                    if ($selectionEntreprise == 'Autre') {
//                        $this->addFlash('info', 'ICI');

                        $errorsEn = $validator->validate($ma->getEntreprise());
                        $errorsMa = $validator->validate($ma->getCompte(), null, array('ajout'));
//                        return new Response(count($errors) );
                        if (count($errorsEn) == 0 && count($errorsMa) == 0) {
                            $addMa = true;
                            $em->persist($ma->getEntreprise());
                        } else {
                            foreach ($errorsEn as &$err) {
                                $input = $err->getPropertyPath();
                                if($input == "codePostal") {
                                    $input = "code_postal";
                                }
                                $form->get('entreprise')->get($input)->addError(new FormError($err->getMessage()));
                            }
                            foreach ($errorsMa as &$err) {
                                $form->get('compte')->get($err->getPropertyPath())->addError(new FormError($err->getMessage()));
                            }
                            $error = true;
                        }
                    }
                    //Entreprise existante choisie
                    if ($selectionEntreprise != 'Autre' && !empty($selectionEntreprise)) {
                        $entreprise = $this->getDoctrine()->getRepository(Entreprise::class)->find($selectionEntreprise);
                        if (!$entreprise) {
                            $this->addFlash('warning', 'Entreprise inexistante');
                            $error = true;
                        } else {
                            $maitres = $this->getDoctrine()->getRepository(MaitreApprentissage::class)->findByEntreprise($entreprise);
                            //Choix autre maitre d'apprentissage
                            if ($selectionMaitre == 'Autre') {
                                $ma->setEntreprise($entreprise);
                                $errors = $validator->validate($ma->getCompte(), null, array('ajout'));

                                if (count($errors) == 0) {

                                    $email = $this->getDoctrine()->getRepository(User::class)->findByEmail($ma->getCompte()->getEmail());

                                    //Si le mail n'est pas déjà utilisé
                                    if (!$email) {
                                        $addMa = true;
                                    } else {
                                        $form->addError(new FormError('Adresse email déjà utilisée'));
                                        $error = true;
                                    }
                                }
                                else {
                                    foreach ($errors as &$err) {
                                        $form->get('compte')->get($err->getPropertyPath())->addError(new FormError($err->getMessage()));
                                    }

                                }
                            }
                            //Maitre d'apprentissage existant choisi
                            if ($selectionMaitre != 'Autre' && !empty($selectionMaitre)) {
                                $ma = $this->getDoctrine()->getRepository(MaitreApprentissage::class)->find($selectionMaitre);
                                if (!$ma) {
                                    $form->addError(new FormError('Maitre d\'apprentissage inexistant'));
                                    $error = true;
                                }
                            }
                        }
                    }
//
                }
                if(!$error) {
                    if($addMa) {
                        $ma->getCompte()->addRole("ROLE_MAITRE_APP");
                        $password = 'password';
                        $encoded = $encoder->encodePassword($user, $password);

                        $user->setPassword($encoded);

                        $user->setEnabled(true);
                        $em->persist($ma->getCompte());
                        $em->persist($ma);
                    }
                    $em->flush();
                    return $this->redirectToRoute('suivi_perso');
                }
            }
        }
        return $this->render('entreprise/entreprise.html.twig', array('entreprises' => $entreprises, 'maitres' => $maitres, 'form' => $form->createView(), 'selectionEntreprise' => $selectionEntreprise, 'selectionMaitre' => $selectionMaitre));
    }


}


