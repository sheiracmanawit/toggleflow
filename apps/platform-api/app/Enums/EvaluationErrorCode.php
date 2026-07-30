<?php

declare(strict_types=1);

namespace App\Enums;

use Symfony\Component\HttpFoundation\Response;

enum EvaluationErrorCode: string
{
    case MissingApiKey = 'MISSING_API_KEY';
    case InvalidApiKey = 'INVALID_API_KEY';
    case EndpointNotFound = 'ENDPOINT_NOT_FOUND';
    case InvalidRequest = 'INVALID_REQUEST';
    case RateLimited = 'RATE_LIMITED';
    case InternalError = 'INTERNAL_ERROR';

    public function message(): string
    {
        return match ($this) {
            self::MissingApiKey => 'An environment API key is required.',
            self::InvalidApiKey => 'The supplied API key is invalid or has been revoked.',
            self::EndpointNotFound => 'The requested API endpoint was not found.',
            self::InvalidRequest => 'The evaluation request is invalid.',
            self::RateLimited => 'Too many evaluation requests. Please try again later.',
            self::InternalError => 'An unexpected error occurred.',
        };
    }

    public function status(): int
    {
        return match ($this) {
            self::MissingApiKey, self::InvalidApiKey => Response::HTTP_UNAUTHORIZED,
            self::EndpointNotFound => Response::HTTP_NOT_FOUND,
            self::InvalidRequest => Response::HTTP_UNPROCESSABLE_ENTITY,
            self::RateLimited => Response::HTTP_TOO_MANY_REQUESTS,
            self::InternalError => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}
