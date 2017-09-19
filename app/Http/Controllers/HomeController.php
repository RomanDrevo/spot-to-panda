<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetCustomersRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Liantech\Panda;

class HomeController extends Controller
{

    /**
     * Customers limit for the SQL query
     * @var integer
     */
    public $LIMIT = 500;

    /**
     * Temporary route (send the customer to the selection view)
     * 
     * @return Redirect
     */
    public function index()
    {
        return redirect("/home");
    }

    /**
     * Builed the view for customers selection
     * 
     * @return View
     */
    public function transfer()
    {
        $campaigns = \DB::connection("spot_db_72_option")->table("campaigns")->whereNotIn("name", ["", "test"])->get();
        $countries = \DB::connection("spot_db_72_option")->table("country")->where("name", "!=", "any")->get();
        $employees = \DB::connection("spot_db_72_option")->table("users")->whereNotIn("firstName", ["", "-", "000", ""])->get();

        return view('transfer', compact("campaigns", "countries", "employees"));
    }

    /**
     * Handles the client request to get customers from Spotoption
     * 
     * @param  GetCustomersRequest $request 
     * @return Response
     */
    public function getCustomers(GetCustomersRequest $request){

        //Build the customer query and execute
        $customers = $this->getCustomersFromRequest($request);

        return response([
            "message" => "ok",
            "customers" => $customers
        ], 200);

    }

    /**
     * Builed the SQL query based on the given request
     * 
     * @param  Request $request 
     * @return String
     */
    public function getCustomersFromRequest(Request $request)
    {
        $startDate = $request->startDate ?
                Carbon::parse($request->startDate)->format("Y-m-d H:i:s") :
                Carbon::now()->startOfMonth()->format("Y-m-d H:i:s");

        $endDate = $request->endDate ?
            Carbon::parse($request->endDate)->format("Y-m-d H:i:s") :
            Carbon::now()->format("Y-m-d H:i:s");

        return \DB::connection("spot_db_72_option")
            ->table("customers")
            ->join("campaigns", "campaigns.id", "=", "customers.campaignId")
            ->join("users", "customers.employeeInChargeId", "=", "users.id")
            ->join("country", "customers.Country", "=", "country.id")
            ->select("customers.id", "customers.FirstName", "customers.LastName", "customers.Country as countryId", "customers.saleStatus", "customers.campaignId", "customers.employeeInChargeId", "campaigns.name as campaignName", "users.firstName as EmployeeFirstName", "users.lastName as EmployeeLastName", "country.name as countryName")
            ->where(function($query) use ($request){
                if($request->has("campaigns") && count($request->campaigns) > 0)
                    $query->whereIn("campaignId", collect($request->campaigns)->pluck("id"));

                if($request->has("countries") && count($request->countries) > 0)
                    $query->whereIn("customers.Country", collect($request->countries)->pluck("id"));

                if($request->has("employees") && count($request->employees) > 0)
                    $query->whereIn("employeeInChargeId", collect($request->employees)->pluck("id"));

                if($request->has("salesStatuses") && count($request->salesStatuses) > 0)
                    $query->whereIn("saleStatus", $request->salesStatuses);
            })
            ->where("regTime", ">", $startDate)
            ->where("regTime", "<=", $endDate)
            ->orderBy("id", "desc")
            ->paginate($request->perPage);
    }

    /**
     * Register all selected customers ro panda
     * 
     * @param  Request $request 
     * @return Response
     */
    public function sendCustomers(Request $request)
    {
        //Create a comma seperated string of all requested customer IDs
        $idsList = implode(",", $request->customersIds);

        //get all customers from Spot Option
        $query = "SELECT *
            FROM customers
            WHERE id IN ({$idsList})";

        $customers = collect(\DB::connection("spot_db_72_option")->select(\DB::raw($query)));

        //register the customers to Panda
        $loggerText = $this->sendCustomersToPanda($customers);

        return response(["message" => $loggerText], 200);
    }

    /**
     * Given a collection of customers we loop through each
     * one and register him to Panda through API
     * 
     * @param  Array $customers 
     * @return String
     */
    public function sendCustomersToPanda($customers)
    {
        //Get an array of countries (based on spot IDs) from the Spot.php config file
        $countries = \Config::get("spot.countries");

        //Initiate a logger string
        $loggerText = "";

        $panda = new Panda(14462, "2ad64c6c850276617d2a732cfc51e70f495843f2f6a5a06b0767c51a9d9dbac8");

        foreach ($customers as $customer) {

            //Register thew customer to panda
            $response = $panda->createCustomer([
                "firstName"         =>  $customer->FirstName,
                "lastName"          =>  $customer->LastName,
                "email"             =>  $customer->email,
                "birthDate"         =>  Carbon::now()->subYears(30)->format("d/m/Y"),
                "country"           =>  isset($countries[$customer->Country]) ? $countries[$customer->Country] : "United Kingdom",
                "phoneNumber"       =>  $customer->Phone,
                "password"          =>  "pass1234",
                "newsletter"        =>  "0",
                "referral"          =>  "source=72option customers"
            ]);

            if($response["code"] == "-1"){

                //Get a list of all panda errors
                $errorCode = array_get($response, "data.errorCode");
                $errorText = array_get($response, "data.error");

                $loggerText .= "<span class='entry-error'>Customer ID: " . $customer->id . " " . $errorText . " (Code: " . $errorCode ."). </span><br/>";
            }else{
                $loggerText .= "<span class='entry-ok'>Customer ID: " . $customer->id . " Added successfully.</span><br/>";
            }
        }

        return $loggerText;
    }
}
