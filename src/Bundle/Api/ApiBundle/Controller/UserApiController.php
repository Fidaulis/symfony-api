<?php
/**
 * Created by PhpStorm.
 * User: julkwel
 * Date: 2/25/19
 * Time: 6:23 PM
 */

namespace App\Bundle\Api\ApiBundle\Controller;


use App\Bundle\User\Entity\User;
use App\Bundle\User\Form\UserType;
use App\Shared\Services\Utils\EntityName;
use App\Shared\Services\Utils\ServiceName;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserApiController extends Controller
{


    /**
     * @param $_name
     * @param $_data
     *
     * @return JsonResponse
     */
    public function response($_name, $_data)
    {
        $_list = new JsonResponse();
        $_list->setData(array($_name => $_data));
        $_list->setStatusCode(200);
        $_list->headers->set('Content-Type', 'application/json');
        $_list->headers->set('Access-Control-Allow-Origin', '*');

        return $_list;
    }

    /**
     * @return \App\Shared\Repository\SkEntityManager|object
     */
    public function userManager()
    {
        return $this->get(ServiceName::SRV_METIER_MANAGER);
    }

    /**
     * @return \App\Shared\Repository\RepositoryTzeRoleManager|object
     */
    public function roleManager()
    {
        return $this->get(ServiceName::SRV_METIER_USER_ROLE);
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function indexAction()
    {
        $_user_manager = $this->userManager();

        $_users = $_user_manager->getAllList(EntityName::USER);

        return $this->response('participant_list', $_users);
    }

    /**
     * @param Request $_request
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function newUserAction(Request $_request)
    {
        $_user = new User();

        $_image = $_request->files->get('image');
        $_role = $this->roleManager()->getTzeRoleById((int) $_request->get('role'));
        $_user->setskRole($_role);
        $_user->setUsrAddress($_request->get('adresse'));
        $_user->setUsrFirstname($_request->get('firstname'));
        $_user->setUsrLastname($_request->get('lastname'));
        $_user->setEmail($_request->get('email'));
        $_user->setUsername($_request->get('username'));
        $_user->setPassword($_request->get('password'));
        $_user->setPassword($_request->get('password'));
        $_user->setUsrPhone($_request->get('phone'));
        $_user->setUsrIsValid($_request->get('isvalid'));
        $this->userManager()->addEntity($_user,$_image);

        try {
            return $this->response('status', Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            return $this->response('status', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param Request $request
     * @return User|null|object|\Symfony\Component\Form\FormInterface|JsonResponse
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateAction(Request $request)
    {
        $user = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UserBundle:User')
            ->find($request->get('id'));
        /* @var $user User */

        if (empty($user)) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(UserType::class, $user);

        $_image = $request->files->get('imgUrl');
        $_role = $this->roleManager()->getTzeRoleById((int) $request->get('skRole'));
        $user->setskRole($_role);
        $form->submit($request->request->all());

        $this->userManager()->addEntity($user,$_image);

        return new JsonResponse(['message' => 'User not biz'], Response::HTTP_ACCEPTED);
    }

}