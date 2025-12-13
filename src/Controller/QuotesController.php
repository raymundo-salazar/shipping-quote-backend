<?php

namespace App\Controller;

use App\Api\Exception\MissingParamsException;
use App\Api\Exception\NotFoundException;
use App\Service\QuoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class QuotesController extends AbstractController
{
    public function __construct(
        private readonly QuoteService $quoteService
    ) {}

    #[Route('/quotes', name: 'api_quotes', methods: ['POST'])]
    public function getQuotes(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            throw new MissingParamsException(['Invalid JSON body']);
        }

        // Validar parámetros requeridos
        $originZipcode = $data['originZipcode'] ?? null;
        $destinationZipcode = $data['destinationZipcode'] ?? null;
        $packageDimensions = $data['packageDimensions'] ?? null;
        $providerId = $data['providerId'] ?? null;

        if (!$originZipcode || !$destinationZipcode || !$packageDimensions || !$providerId) {
            throw new MissingParamsException(['originZipcode', 'destinationZipcode', 'packageDimensions', 'providerId']);
        }

        // Validar dimensiones del paquete
        $requiredDimensions = ['weight', 'length', 'width', 'height'];
        foreach ($requiredDimensions as $dim) {
            if (!isset($packageDimensions[$dim])) {
                throw new MissingParamsException(["packageDimensions.{$dim}"]);
            }
        }

        // Parámetro opcional: provider_id

        // Usuario actual (null si no hay sesión)
        $user = $this->getUser();

        // Obtener cotizaciones
        $quotes = $this->quoteService->getQuotes(
            originZipcode: $originZipcode,
            destinationZipcode: $destinationZipcode,
            packageDimensions: [
                'weight' => (float) $packageDimensions['weight'],
                'length' => (float) $packageDimensions['length'],
                'width' => (float) $packageDimensions['width'],
                'height' => (float) $packageDimensions['height'],
            ],
            user: $user,
            providerId: (int) $providerId
        );

        if (empty($quotes)) {
            throw new NotFoundException('No quotes available for the given parameters');
        }

        return new JsonResponse([
            'quotes' => $quotes,
        ], Response::HTTP_OK);
    }
}
