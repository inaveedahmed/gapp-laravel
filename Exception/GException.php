<?php

namespace Ipaas\Gapp\Exception;

use Exception;
use Google\Cloud\ErrorReporting\Bootstrap;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GException extends ExceptionHandler
{

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  Exception $exception
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        if (isset($_SERVER['GAE_SERVICE'])) {
            try { // try context from helper in-case helper is not loaded
                $context = ilog()->toArray();
            } catch (Exception $exception) {
                $context = "Unable to get context";
            }

            if ($logger = Bootstrap::$psrLogger) {
                $message = sprintf('PHP Notice: %s', (string)$exception);
                $service = $logger->getMetadataProvider()->serviceId();
                $version = $logger->getMetadataProvider()->versionId();

                $logger->error(
                    $message,
                    [
                        'serviceContext' => [
                            'service' => $service,
                            'version' => $version,
                        ],
                        'context' => $context
                    ]
                );
            } else {
                $message = sprintf('PHP Notice: %s', (string)$exception->getMessage());
                file_put_contents('php://stderr', $message . PHP_EOL, FILE_APPEND);
            }
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  Exception  $exception
     * @return Response
     */
    public function render($request, Exception $exception)
    {
        $parentMessage = $exception->getMessage();

        if ($exception->getPrevious() instanceof Exception) {
            $exception = $exception->getPrevious();
        }

        // If the request wants JSON (AJAX doesn't always want JSON)
        if ($request->expectsJson() || $request->isJson()) {
            return JsonExceptionRender::render(
                $exception,
                $parentMessage
            );
        }

        return parent::render($request, $exception);
    }
}
