<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    #[Route('/post/name', name: 'app_post')]
    public function index(): Response
    {
        return $this->render('post/index.html.twig', [
            'controller_name' => 'PostController',
        ]);
    }

    #[Route('/posts/{slug}', name: 'show', methods: ['GET'])]
    public function showPost(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/post/{slug}/edit', name: 'post_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        Post $post
    ): Response {
        // Edit form + process
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('image')->getData()) {
                $imageFile = $form->get('image')->getData();
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    $post->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors de l\'upload de votre Image');
                }
            }

            $post->setSlug($form->get('slug')->getData());
            $em->persist($post);
            $em->flush();

            // Redirigez l'utilisateur vers la page de détails du post édité
            return $this->redirectToRoute('show', ['slug' => $post->getSlug()]);
        }

        // Return the view
        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'editForm' => $form->createView(),
        ]);
    }
}
