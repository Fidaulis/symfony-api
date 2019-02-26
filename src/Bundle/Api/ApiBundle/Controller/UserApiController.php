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
use App\Shared\Services\Utils\RoleName;
use App\Shared\Services\Utils\ServiceName;
use Doctrine\ORM\ORMException;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
     * Get List User
     * @return JsonResponse
     * @throws \Exception
     */
    public function indexAction()
    {
        $_user_manager = $this->userManager();

        $_users = $_user_manager->getAllList(EntityName::USER);

        return $this->response('user_list', $_users);
    }

    /**
     * Add user
     * @param Request $_request
     * @return JsonResponse
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function newUserAction(Request $_request)
    {
        $_user = new User();
        $form = $this->createForm(UserType::class, $_user);
        $_image = $_request->files->get('imgUrl');

        /*
         * Set role
         */
        $_type = $this->roleManager()->getTzeRoleById((int) $_request->get('skRole'));
        $_role = RoleName::$ROLE_TYPE[$_type->getRlName()];
        $_user->setRoles(array($_role));

        /*
         * Encrypted password
         */
        $plainPassword = $_request->get('password');
        $_user->setPassword($plainPassword);

        /*
         * submit new user
         */
        $form->submit($_request->request->all());
        $this->userManager()->addEntity($_user,$_image);

        try {
            return $this->response('status', Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            return $this->response('status', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update user
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

        return $this->response('status', Response::HTTP_ACCEPTED);
    }

}