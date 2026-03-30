<?php declare(strict_types=1);

namespace DeRidderDenHertog;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Authentication\Failure\CouldNotAuthenticate;
use DeRidderDenHertog\Core\Failure\UnknownException;
use DeRidderDenHertog\Core\Failure\ValidationException;
use DeRidderDenHertog\Core\Http\DeRidderDenHertogConnector;
use DeRidderDenHertog\Core\Http\Request;
use DeRidderDenHertog\Core\Http\Result;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;
use DeRidderDenHertog\Core\Type\Parameter\PerPage;
use DeRidderDenHertog\Core\Type\Primitive\CustomerId;
use DeRidderDenHertog\DeleteCustomer\Failure\CouldNotDeleteCustomer;
use DeRidderDenHertog\DeleteCustomer\Request\DeleteCustomer;
use DeRidderDenHertog\GetApiFunctions\Failure\CouldNotGetApiFunctions;
use DeRidderDenHertog\GetApiFunctions\Request\GetApiFunctions;
use DeRidderDenHertog\GetApiFunctions\Type\ApiFunctions;
use DeRidderDenHertog\GetApiFunctions\Type\Mapping\ApiFunctionMapper;
use DeRidderDenHertog\GetCustomers\Failure\CouldNotGetCustomers;
use DeRidderDenHertog\GetCustomers\Request\GetCustomers;
use DeRidderDenHertog\GetCustomers\Type\Customer;
use DeRidderDenHertog\GetCustomers\Type\Customers;
use DeRidderDenHertog\GetCustomers\Type\Mapping\CustomerMapper;
use DeRidderDenHertog\GetCustomers\Type\Parameter\Fields;
use DeRidderDenHertog\GetDayTurnover\Failure\CouldNotGetDayTurnover;
use DeRidderDenHertog\GetDayTurnover\Request\GetDayTurnover;
use DeRidderDenHertog\GetDayTurnover\Type\DayTurnoverPage;
use DeRidderDenHertog\GetDayTurnover\Type\Mapping\TransactionMapper;
use DeRidderDenHertog\GetDayTurnover\Type\Parameter\Cursor;
use DeRidderDenHertog\GetDayTurnover\Type\Transactions;
use DeRidderDenHertog\SetCustomer\Failure\CouldNotSetCustomer;
use DeRidderDenHertog\SetCustomer\Request\SetCustomer;
use DeRidderDenHertog\SetCustomer\Type\Parameter\CustomerData;
use Saloon\Http\Response;
use Throwable;

final readonly class DeRidderDenHertog
{
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
            request: new DeleteCustomer($id)->setGuid($this->guid),
            onFailure: CouldNotDeleteCustomer::class,
        );

        return true;
    }

    /**
     * These are the API functions authorized for this APIGuid, for support send a email to jflietstra@kwik-bit.nl.
     *
     * @return ApiFunctions
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetApiFunctions
     * @throws UnknownException
     * @throws ValidationException
     */
    public function getApiFunctions(): ApiFunctions
    {
        $result = $this->send(
            request: new GetApiFunctions()->setGuid($this->guid),
            onFailure: CouldNotGetApiFunctions::class,
        );

        return ApiFunctions::of(
            array_map(new ApiFunctionMapper(), $result->records['APIFunctions'])
        );
    }

    /**
     * If you do not want to retrieve all fields, you can specify the Fields option with the fields you wish to retrieve separated by commas.
     *
     * @param Filter|null $filter The SQL filter to apply.
     * @param Date|null $from The date from which to retrieve customers.
     *
     * @return Customers
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetCustomers
     * @throws UnknownException
     * @throws ValidationException
     */
    public function getCustomers(?Fields $fields = null, ?Filter $filter = null, ?Date $from = null): Customers
    {
        $result = $this->send(
            request: new GetCustomers($fields, $filter, $from)->setGuid($this->guid),
            onFailure: CouldNotGetCustomers::class,
        );

        return Customers::of(
            array_map(new CustomerMapper(), $result->records['TblKlanten'] ?? [])
        );
    }

    /**
     * The daily turnover can be retrieved with a FromDate, TillDate as parameter.
     *
     * @param Filter|null $filter The SQL filter to apply.
     * @param Date|null $from The date from which to retrieve transactions.
     * @param Date|null $till The date until which to retrieve transactions.
     *
     * @return Transactions
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetDayTurnover
     * @throws UnknownException
     * @throws ValidationException
     */
    public function getDayTurnover(?Filter $filter = null, ?Date $from = null, ?Date $till = null): Transactions
    {
        $result = $this->send(
            request: new GetDayTurnover($filter, $from, $till)->setGuid($this->guid),
            onFailure: CouldNotGetDayTurnover::class,
        );

        return Transactions::of(
            array_map(new TransactionMapper(), $result->records['Kassabonnen'] ?? [])
        );
    }

    /**
     * Fetch a single page of daily turnover. Pass null for cursor to start from the beginning.
     *
     * @param PerPage $perPage The number of results per page.
     * @param Cursor|null $cursor The cursor position to resume from, or null for the first page.
     * @param Filter|null $filter The SQL filter to apply.
     * @param Date|null $from The date from which to retrieve transactions.
     * @param Date|null $till The date until which to retrieve transactions.
     *
     * @return DayTurnoverPage
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetDayTurnover
     * @throws UnknownException
     * @throws ValidationException
     */
    public function getDayTurnoverPage(
        PerPage $perPage,
        ?Cursor $cursor = null,
        ?Filter $filter = null,
        ?Date $from = null,
        ?Date $till = null,
    ): DayTurnoverPage {
        $result = $this->send(
            request: new GetDayTurnover($filter, $from, $till)
                ->forPage($perPage, $cursor)
                ->setGuid($this->guid),
            onFailure: CouldNotGetDayTurnover::class,
        );

        $transactions = Transactions::of(
            array_map(new TransactionMapper(), $result->records['Kassabonnen'] ?? [])
        );

        $nextCursor = Cursor::define($result->raw['Lastrecord'], $result->raw['NbRecords'] ?? 0, $perPage);

        return DayTurnoverPage::of($transactions, $nextCursor);
    }

    /**
     * The daily turnover can be retrieved with a FromDate, TillDate as parameter.
     *
     * @param PerPage $perPage The number of results per page.
     * @param Filter|null $filter The SQL filter to apply.
     * @param Date|null $from The date from which to retrieve transactions.
     * @param Date|null $till The date until which to retrieve transactions.
     *
     * @return iterable<Transactions>
     *
     * @throws CouldNotAuthenticate
     * @throws CouldNotGetDayTurnover
     * @throws ValidationException
     */
    public function getDayTurnoverPaginated(
        PerPage $perPage,
        ?Filter $filter = null,
        ?Date $from = null,
        ?Date $till = null
    ): iterable {
        $paginator = $this->paginate(
            request: new GetDayTurnover($filter, $from, $till)->setGuid($this->guid),
            onFailure: CouldNotGetDayTurnover::class,
            perPage: $perPage,
        );

        $map = new TransactionMapper();

        foreach ($paginator as $result) {
            yield Transactions::of(
                array_map($map, $result->records['Kassabonnen'] ?? [])
            );
        }
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
        $result = $this->send(
            request: new SetCustomer($data)->setGuid($this->guid),
            onFailure: CouldNotSetCustomer::class,
        );

        return CustomerId::fromInteger($result->raw['CustomerID']);
    }

    /**
     * @param Request $request
     * @param class-string<ValidationException> $onFailure
     * @param PerPage $perPage
     *
     * @return iterable<Result>
     *
     * @throws CouldNotAuthenticate
     * @throws ValidationException
     */
    protected function paginate(Request $request, string $onFailure, PerPage $perPage): iterable
    {
        $paginator = $this->client->paginate($request)->setPerPageLimit($perPage->toInteger());

        foreach ($paginator as $response) {
            yield $this->getResult($response, $onFailure);
        }
    }

    /**
     * @param Request $request
     * @param class-string<ValidationException> $onFailure
     *
     * @return Result
     *
     * @throws CouldNotAuthenticate
     * @throws UnknownException
     * @throws ValidationException
     */
    protected function send(Request $request, string $onFailure): Result
    {
        try {
            $response = $this->client->send($request);
        } catch (Throwable $ex) {
            throw UnknownException::sorry($ex);
        }

        return $this->getResult($response, $onFailure);
    }

    /**
     * @param Response $response
     * @param class-string<ValidationException> $onFailure
     *
     * @return Result
     *
     * @throws CouldNotAuthenticate
     * @throws ValidationException
     */
    private function getResult(Response $response, string $onFailure): Result
    {
        $result = $response->dto();

        if (CouldNotAuthenticate::isSatisfiedBy($result->answer)) {
            throw CouldNotAuthenticate::becauseTheDatabaseGuidIsNotValid();
        }

        if (! $result->ok) {
            throw new $onFailure($result->answer);
        }

        return $result;
    }
}
