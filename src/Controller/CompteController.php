<?php

namespace App\Controller;

use App\Entity\Apprenti;
use App\Entity\DossierApprenti;
use App\Entity\EtapeDossier;
use App\Entity\MaitreApprentissage;
use App\Entity\User;
use App\Entity\TypeEtape;
use App\Form\CompteType;
use App\Form\MaitreApprentissageProfilType;
use App\Form\UtilisateurType;
use App\Form\ApprentiType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route; //To define the route to access it
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted; //Pour vérifier si l'utilisateur est autorisé a accéder à la page
use Symfony\Component\Security\Core\Exception\AccessDeniedException; //Erreur 403
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface; //Pour vérifier les droits
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class CompteController extends Controller
{

    /**
     * Affichage de la page de choix d'ajout de compte
     *
     * @Route("/compte/gestion/", name="gestion_compte")
     * @IsGranted("ROLE_IUT")
     */
    public function gestion_compte() {
        return $this->render('compte/gestion_compte.html.twig');
    }

    /**
     * Formulaire d'ajout de apprenti/cfa/iut
     *
     * @Route("/compte/ajout/{type}", name="ajout_compte", requirements={"type"="(apprenti|cfa|iut)"})
     * @IsGranted("ROLE_IUT")
     */
    public function ajout_compte(AuthorizationCheckerInterface $authChecker, Request $request, UserPasswordEncoderInterface $encoder, $type)
    {
        $user = new User();
        //On détermine quel type de compte on va créer
        if($type == "apprenti") {
            $title = "apprenti";
        } elseif($type == "cfa") {
            $title = "CFA";
        }
        //Si on souhaite ajouter un compte IUT, on vérifie si le compte connecté possède les droits
        elseif($type == "iut" && $authChecker->isGranted('ROLE_ADMIN')) {
            $title = "IUT";
        } else {
            throw new AccessDeniedException();
        }

        //On créer le formulaire
        $form = $this->createForm(CompteType::class, $user);

        //Dans le cas si on souhaite créer d'autres comptes administrateur
//        if($type == "iut") {
//            $form = $form->add('administrateur',   CheckboxType::class, array('required' => false));
//        }

        $form = $form->add('ajouter', SubmitType::class);

        // Si la requête est en POST (donc que le formulaire à été validé)
        if ($request->isMethod('POST')) {

            // À partir de maintenant, la variable $compte contient les valeurs entrées dans le formulaire par l'utilisateur
            $form->handleRequest($request);

            $email_exist = false;
            $userManager = $this->get('fos_user.user_manager');

            //On vérifie s'il n'y a pas déjà un compte lié à cette adresse mail
            $exists = $userManager->findUserBy(array('email' => $user->getEmail()));
            if ($exists instanceof User) {
                $email_exist = true;            }

            // On vérifie que les valeurs entrées sont correctes
            if ($form->isSubmitted() && $form->isValid() && !$email_exist) {

                //On génére le mot de passe et on envoi un mails avec les identifiants
                $this->get('app.emailservice')->nouveau_compte($user);

                $user->setEnabled(true);

                if($type == "apprenti") {
                    $user->addRole('ROLE_APPRENTI');
                }
                elseif ($type == "cfa") {
                    $user->addRole('ROLE_CFA');
                }
                elseif ($type == "iut") {
                    $user->addRole('ROLE_IUT');
                }

                // On enregistre notre objet $compte dans la base de données,
                $em = $this->getDoctrine()->getManager();

                $em->persist($user);

                //On créer et rattache un dossier à l'apprenti
                if($type == "apprenti") {
                    $dossier = new DossierApprenti();
                    $dossier->setEtat("En cours");
                    $em->persist($dossier);

                    $role = new Apprenti();
                    $role->setCompte($user);
                    $role->setDossierApprenti($dossier);
                    $em->persist($role);

                    $etape_dossier = new EtapeDossier();
                    $etape_dossier->setDossier($dossier);
                    $etape_dossier->setTypeEtape($this->getDoctrine()->getRepository(TypeEtape::class)->find(1));
                    $em->persist($etape_dossier);
                    $dossier->setEtapeActuelle($etape_dossier);
                }
                $em->flush();

                //On affiche un message de succès
                $this->addFlash('success', 'Compte bien enregistré.');

                // On redirige vers la même page pour donner la possibilité d'ajouter d'autres comptes
                return $this->redirectToRoute('ajout_compte', array('type' => $type));
            }
            //Si le mail est déjà utilisé on affiche a message d'erreur
            if($email_exist) {
                $form->get('email')->addError(new FormError('Un compte lié à cet email existe déjà.'));
            }
        }

        //Soit on viens d'arriver sur la page, soit le formulaire contient des données incorrectes
        return $this->render('compte/ajout.html.twig', array('title' => $title,
            'form' => $form->createView(),
        ));
    }

    /**
     * Edition du compte de l'apprenti
     *
     * @Route("/compte/edition/", name="edition_compte_perso")
     */
    public function edition_compte_perso(AuthorizationCheckerInterface $authChecker, Request $request)
    {
        if($authChecker->isGranted('ROLE_APPRENTI') || $authChecker->isGranted('ROLE_MAITRE_APP'))
            $id = $this->getUser()->getId();
        return $this->edition_compte($authChecker, $request, $id);
    }

    /**
     * Edition du compte de l'apprenti
     *
     * @Route("/compte/edition/{id}", name="edition_compte", requirements={"id"="\d+"})
     */
    public function edition_compte(AuthorizationCheckerInterface $authChecker, Request $request, $id) {
        $title = "Edition du compte";

        //On vérifie que l'utilisateur connecté est un iut ou que le compte a modifier est bien celui de l'utilisateur connecté
        if(!($authChecker->isGranted('ROLE_IUT') || $this->getUser()->getId()==$id)) {
            throw new AccessDeniedException();
        }

        //On récupère l'user
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if(!$user) {
            return $this->render('message.html.twig', array(
                'typeMessage' => "Utilisateur non trouvé", 'message' => 'Pas d`\'utilisateur trouvé pour l\'ID ' . $id
            ));
        }

        if($authChecker->isGranted('ROLE_IUT')) {
            $id_apprenti = $request->get('app');
            if (!empty($id_apprenti)) {
                $lien = $url = $this->generateUrl(
                    'choix_entreprise',
                    array('id' => $id_apprenti,
                        'src' => 'bordereau')
                );
                $this->addFlash('warning', "Attention, les modifications apportées à ce maître d'apprentissage seront appliquées à tous les apprentis rattachés à cette personne. 
                Si vous souhaitez changer le maître d'appentissage de l'apprenti, merci de passer par ce <a href=\"" . $lien . "\">lien</a>.");
            }
        }
        if($user->hasRole('ROLE_APPRENTI')) {
            $apprenti = $this->getDoctrine()->getRepository(Apprenti::class)->find($id);
            $form = $this->createForm(ApprentiType::class, $apprenti);
        }
        else if($user->hasRole('ROLE_MAITRE_APP')) {
            $ma = $this->getDoctrine()->getRepository(MaitreApprentissage::class)->find($id);
            $form = $this->createForm(MaitreApprentissageProfilType::class, $ma);
        }
        else {
            $form = $this->createForm(CompteType::class, $user)
                ->add('Enregistrer', SubmitType::class);

        }

        // Si la requête est en POST (donc que le formulaire à été validé)
        if ($request->isMethod('POST')) {

            //On copie l'email actuel du compte pour déterminer plus loin si l'utilisateur a changer le mail du compte
            $old_email = "".$user->getEmail();
            // À partir de maintenant, la variable $compte contient les valeurs entrées dans le formulaire par le visiteur
            $form->handleRequest($request);

            $email_ok = true;
            //On vérifie s'il n'y a pas déjà un compte lié à cette adresse mail
            if($old_email != $user->getEmail()) {
                $email_exist = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($apprenti->getCompte()->getEmail());
                if($email_exist) {
                    $email_ok = false;
                }
            }

            // On vérifie que les valeurs entrées sont correctes
            if ($form->isValid() && $email_ok) {

                // On enregistre notre objet $compte dans la base de données,
                $em = $this->getDoctrine()->getManager();

                $em->flush();

                $this->addFlash('success', 'Modifications bien enregistrées.');

                if($request->get('src') == "bordereau") {
                    return $this->redirectToRoute('consulter_bordereau', array('id' => $request->get('app')));
                }
                else {
                    // On redirige vers le profil
                    return $this->redirectToRoute('profil', array('id' => $id));
                }
            }
            if(!$email_ok) {
                $form->get('compte')->get('email')->addError(new FormError('Un compte lié à cet email existe déjà.'));
            }
        }



        return $this->render('compte/edition_compte.html.twig', array('title' => $title,
            'form' => $form->createView(),
        ));
    }


    /**
     * Affichage de la page de profil perso
     *
     * @Route("/compte/profil/", name="profil_perso")
     */
    public function profil_perso(AuthorizationCheckerInterface $authChecker) {
        $id = $this->getUser()->getId();
        return $this->profil($authChecker, $id);
    }

    /**
     * Affichage de la page de profil
     *
     * @Route("/compte/profil/{id}", name="profil", requirements={"id"="\d+"})
     */
    public function profil(AuthorizationCheckerInterface $authChecker, $id) {

        $edit = false;
        $delete = false;
        $apprenti = null;
        $maitreapp = null;

        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if(!$user) {
            return $this->render('message.html.twig', array(
                'typeMessage' => "Utilisateur non trouvé", 'message' => 'Pas d`\'utilisateur trouvé pour l\'ID ' . $id
            ));
        }
        if ($this->getUser()->getId()==$id || ($authChecker->isGranted('ROLE_IUT'))) {
            if($user->hasRole('ROLE_ADMIN') && !$authChecker->isGranted('ROLE_ADMIN')) {
                $edit = false;
            }
            else {
                $edit = true;
            }
            if($authChecker->isGranted('ROLE_ADMIN')) {
                if($user->hasRole('ROLE_APPRENTI') || $user->hasRole('ROLE_CFA') || $user->hasRole('ROLE_IUT'))
                    $delete = true;
            }
        }

        if($user->hasRole('ROLE_APPRENTI')) {
            if(($authChecker->isGranted('ROLE_APPRENTI') && $this->getUser()->getID()!=$user->getId())) {
                throw new AccessDeniedException();
            }
            $apprenti = $this->getDoctrine()->getRepository(Apprenti::class)->find($id);
            if ($authChecker->isGranted('ROLE_MAITRE_APP')) {
                $ma_exist = !empty($apprenti->getDossier()->getMaitreApprentissage());
                if($ma_exist) {
                    $ma = $apprenti->getDossier()->getMaitreApprentissage()->getCompte()->getId();
                }
                if (!$ma_exist || $ma != $this->getUser()->getId()) {
                    throw new AccessDeniedException();
                }
            }

        } elseif ($user->hasRole('ROLE_MAITRE_APP')) {
            $maitreapp = $this->getDoctrine()->getRepository(MaitreApprentissage::class)->find($id);
        }

        return $this->render('compte/profil.html.twig', array(
            'user' => $user, 'apprenti' => $apprenti, 'maitreapp' => $maitreapp, 'edit' => $edit, 'delete' => $delete
        ));
    }
    /**
     * Suppression d'un compte
     *
     * @Route("/compte/suppression/{id}", name="suppression_compte", requirements={"id"="\d+"})
     */
    public function suppression_compte(Request $request, AuthorizationCheckerInterface $authChecker, $id) {
        if(!$authChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }

        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if(!$user) {
            throw new NotFoundHttpException();
        }
        if ($user->hasRole('ROLE_APPRENTI') || $user->hasRole('ROLE_CFA') || $user->hasRole('ROLE_IUT')) {
            $resp = $request->query->get(
                'resp');
            if($resp == "true") {
                $em = $this->getDoctrine()->getEntityManager();

                if($user->hasRole('ROLE_APPRENTI')) {
                    $apprenti = $this->getDoctrine()->getRepository(Apprenti::class)->findOneByCompte($user->getId());
                    $liste_etapes = $this->getDoctrine()->getRepository(EtapeDossier::class)->findByDossier($apprenti->getDossier()->getId());
                    foreach ($liste_etapes as &$etape) {
                        $em->remove($etape);
                    }
                    $em->remove($apprenti->getDossier());
                    $em->remove($apprenti);
                }

                $em->remove($user);
                $em->flush();

                $this->addFlash('success', "Compte supprimé avec succès.");

                if($user->hasRole('ROLE_APPRENTI')) {
                    return $this->redirectToRoute('liste');
                } else {
                    return $this->redirectToRoute('gestion_compte');
                }
            } elseif($resp == "false") {
                return $this->redirectToRoute('profil', array('id' => $id));
            }
        }
        return $this->render('compte/suppression.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * Liste des comptes par type
     *
     * @Route("/compte/liste/{type}", name="liste_comptes", requirements={"type"="(cfa|iut)"})
     * @IsGranted("ROLE_IUT")
     */
    public function liste_comptes(Request $request, AuthorizationCheckerInterface $authChecker, $type) {
        $role = null;
        if($type == "cfa") {
            $liste_users = $this->getDoctrine()
                ->getRepository(User::class)
                ->findByRole("ROLE_CFA");
            $type = "CFA";
        }
        elseif($type == "iut") {
            $liste_users = $this->getDoctrine()
                ->getRepository(User::class)
                ->findAllIUT();
            $type = "IUT";
        }
        else {
            throw new AccessDeniedException();
        }

        return $this->render('compte/liste_users.html.twig', array('liste_users' => $liste_users, 'type' => $type));
    }


}
