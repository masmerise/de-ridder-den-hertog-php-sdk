<?php declare(strict_types=1);

namespace DeRidderDenHertog;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Authentication\Failure\CouldNotAuthenticate;
use DeRidderDenHertog\Core\Failure\UnknownException;
use DeRidderDenHertog\Core\Failure\ValidationException;
use DeRidderDenHertog\Core\Http\DeRidderDenHertogConnector;
use DeRidderDenHertog\Core\Http\Soap\Mapping\MapsResponses;
use DeRidderDenHertog\Core\Http\Soap\Message;
use DeRidderDenHertog\Core\Http\Soap\Request;
use DeRidderDenHertog\Core\Http\Soap\Response;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;
use DeRidderDenHertog\Core\Type\Primitive\CustomerId;
use DeRidderDenHertog\DeleteCustomer\Failure\CouldNotDeleteCustomer;
use DeRidderDenHertog\DeleteCustomer\Request\DeleteCustomer;
use DeRidderDenHertog\GetApiFunctions\Failure\CouldNotGetApiFunctions;
use DeRidderDenHertog\GetApiFunctions\Request\GetApiFunctions;
use DeRidderDenHertog\GetApiFunctions\Type\ApiFunction;
use DeRidderDenHertog\GetApiFunctions\Type\Mapping\MapsApiFunctions;
use DeRidderDenHertog\GetCustomers\Failure\CouldNotGetCustomers;
use DeRidderDenHertog\GetCustomers\Request\GetCustomers;
use DeRidderDenHertog\GetCustomers\Type\Customer;
use DeRidderDenHertog\GetCustomers\Type\Mapping\MapsCustomers;
use DeRidderDenHertog\GetCustomers\Type\Parameter\Fields;
use DeRidderDenHertog\GetDayTurnover\Failure\CouldNotGetDayTurnover;
use DeRidderDenHertog\GetDayTurnover\Request\GetDayTurnover;
use DeRidderDenHertog\GetDayTurnover\Type\Mapping\MapsTransactions;
use DeRidderDenHertog\GetDayTurnover\Type\Transaction;
use DeRidderDenHertog\SetCustomer\Failure\CouldNotSetCustomer;
use DeRidderDenHertog\SetCustomer\Request\SetCustomer;
use DeRidderDenHertog\SetCustomer\Type\Parameter\CustomerData;
use Throwable;

final readonly class DeRidderDenHertog
{
    use MapsApiFunctions;
    use MapsCustomers;
    use MapsResponses;
    use MapsTransactions;

    private DeRidderDenHertogConnector $client;

    private function __construct(private ApiGuid $guid)
    {
        $this->client = new DeRidderDenHertogConnector();
    }

    public static function authenticate(ApiGuid $guid): DeRidderDenHertog
    {
        return new self($guid);
    }

    /**
     * Delete a customer.
     *
     * @param CustomerId $id The ID of the customer to delete.
     *
     * @return true
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotDeleteCustomer
     * @throws UnknownException
     * @throws ValidationException
     */
    public function deleteCustomer(CustomerId $id): true
    {
        $this->send(
            request: new DeleteCustomer($this->guid, $id),
            onFailure: CouldNotDeleteCustomer::class,
        );

        return true;
    }

    /**
     * These are the API functions authorized for this APIGuid, for support send a email to jflietstra@kwik-bit.nl.
     *
     * @return ApiFunction[]
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetApiFunctions
     * @throws UnknownException
     * @throws ValidationException
     */
    public function getApiFunctions(): array
    {
        $response = $this->send(
            request: new GetApiFunctions($this->guid),
            onFailure: CouldNotGetApiFunctions::class,
        );

        return array_map($this->toApiFunction(...), $response->records['APIFunctions']);
    }

    /**
     * If you do not want to retrieve all fields, you can specify the Fields option with the fields you wish to retrieve separated by commas.
     *
     * @param Filter|null $filter The SQL filter to apply.
     * @param Date|null $from The date from which to retrieve customers.
     *
     * @return Customer[]
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetCustomers
     * @throws UnknownException
     * @throws ValidationException
     */
    public function getCustomers(?Fields $fields = null, ?Filter $filter = null, ?Date $from = null): array
    {
        $response = $this->send(
            request: new GetCustomers($this->guid, $fields, $filter, $from),
            onFailure: CouldNotGetCustomers::class,
        );

        return array_map($this->toCustomer(...), $response->records['TblKlanten'] ?? []);
    }

    /**
     * The daily turnover can be retrieved with a FromDate, TillDate as parameter.
     *
     * @param Filter|null $filter The SQL filter to apply.
     * @param Date|null $from The date from which to retrieve transactions.
     * @param Date|null $till The date until which to retrieve transactions.
     *
     * @return Transaction[]
     */
    public function getDayTurnover(?Filter $filter = null, ?Date $from = null, ?Date $till = null): array
    {
        $response = $this->send(
            request: new GetDayTurnover($this->guid, $filter, $from, $till),
            onFailure: CouldNotGetDayTurnover::class,
        );

        return array_map($this->toTransaction(...), $response->records['Kassabonnen'] ?? []);
    }

    /**
     * Add or Change a customer.
     *
     * @param CustomerData $data The data to set.
     *
     * @return CustomerId
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetCustomers
     * @throws UnknownException
     * @throws ValidationException
     */
    public function setCustomer(CustomerData $data): CustomerId
    {
        $response = $this->send(
            request: new SetCustomer($this->guid, $data),
            onFailure: CouldNotSetCustomer::class,
        );

        return CustomerId::fromInteger($response->raw['CustomerID']);
    }

    /**
     * @param Request $request
     * @param class-string<ValidationException> $onFailure
     *
     * @return Response
     *
     * @throws CouldNotAuthenticate
     * @throws UnknownException
     * @throws ValidationException
     */
    private function send(Request $request, string $onFailure): Response
    {
        try {
            $response = $this->client->send($request);

            $message = $response->xmlReader()->value('RHDataServiceResult')->sole();
            $message = Message::decode($message);
        } catch (Throwable $ex) {
            throw UnknownException::sorry($ex);
        }

        $response = $this->toResponse($message);

        if (CouldNotAuthenticate::isSatisfiedBy($response->answer)) {
            throw CouldNotAuthenticate::becauseTheDatabaseGuidIsNotValid();
        }

        if (! $response->ok) {
            throw new $onFailure($response->answer);
        }

        return $response;
    }
}
