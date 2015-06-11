<?php

class Car {

	public $cost;
	public $country;
	public $engine;

	public function __construct($cost, $country = 'india')
	{
		$this->cost = $cost;
		$this->country = $country;
	}

	public function setEngine(Engine $engine)
	{
		$this->engine = $engine;
	}	
}

class Engine {

	public $type;	
	public $capacity;

	public function __construct($capacity, $type = 'fuel')
	{
		$this->type = $type;
		$this->capacity = $capacity;	
	}
	public function getCapacity()
	{
		return $this->capacity;		
	}
	public function getCapacityInCc()
	{
		return $this->capacity . 'cc';
	}
	public function getType()
	{
		return $this->type;
	}
}

class TaxCalculator {

	public $car;
	public $salesTaxPercentage = 0;
	public $customsDutyPercentage = 0;
	public $greenTaxPercentage = 0;
	public $quotaDifference;
	public $quota = false;
	public $quotaLimit;

	public function __construct(Car $car)
	{
		$this->car = $car;
	}
	public function setQuota($limit = 800000)
	{
		$this->quotaLimit = $limit;
		$this->quotaDifference = ($this->car->cost - $limit) < 0 ? 0 : ($this->car->cost - $limit);
		$this->quota = true;
		return $this;
	}
	public function removeQuota()
	{
		$this->quota = false;
		return $this;
	}
	private function withQuota()
	{
		return $this->quota;
	}
	public function computeTax()
	{
		if ($this->car->engine->type == 'electric') {
			$this->salesTaxPercentage = 0;
			$this->customsDutyPercentage = 0;			
		} else if ($this->car->engine->type == 'hybrid') {
			$this->salesTaxPercentage = 20;
			$this->customsDutyPercentage = 20;
			$this->greenTaxPercentage = 5;
		} else {
			if ($this->car->country == 'india') {
				$this->salesTaxPercentage = $this->car->engine->capacity < 1500 ? 45 : 50;
				$this->greenTaxPercentage = 10;
			} else {
				switch (true) {
					case ($this->car->engine->capacity < 1500):
						$this->salesTaxPercentage = 45;
						$this->customsDutyPercentage = 45;
						$this->greenTaxPercentage = 10;
						break;
					case ($this->car->engine->capacity >= 1500 && $this->car->engine->capacity < 1800):
						$this->salesTaxPercentage = 50;
						$this->customsDutyPercentage = 50;
						$this->greenTaxPercentage = 15;
						break;
					case ($this->car->engine->capacity >= 1800 && $this->car->engine->capacity < 2500):
						$this->salesTaxPercentage = 50;
						$this->customsDutyPercentage = 50;
						$this->greenTaxPercentage = 20;
						break;
					case ($this->car->engine->capacity >= 2500 && $this->car->engine->capacity < 3000):
						$this->salesTaxPercentage = 50;
						$this->customsDutyPercentage = 50;
						$this->greenTaxPercentage = 25;
						break;
					default:
						$this->salesTaxPercentage = 50;
						$this->customsDutyPercentage = 100;
						$this->greenTaxPercentage = 30;
						break;
				}
			}

		}
	}
	public function getSalesTaxPercentage()
	{
		return $this->salesTaxPercentage;
	}	
	public function getCustomsDutyPercentage()
	{
		return $this->customsDutyPercentage;
	}
	public function getGreenTaxPercentage()
	{
		return $this->greenTaxPercentage;
	}
	public function getSalesTax()
	{
		$multiplier = $this->withQuota() ? $this->quotaDifference : $this->car->cost;
		return $multiplier * $this->salesTaxPercentage/100;
		
	}
	public function getCustomsDuty()
	{
		$multiplier = $this->withQuota() ? $this->quotaDifference : $this->car->cost;
		return $multiplier * $this->customsDutyPercentage/100;
	}

	public function getGreenTax()
	{
		return $this->car->cost * $this->greenTaxPercentage/100; //quota has no effect
	}

	public function getTotalTax()
	{
		$this->computeTax();
		return $this->getSalesTax() + $this->getGreenTax() + $this->getCustomsDuty();
	}
	public function getTotalCost()
	{		
		return $this->car->cost + $this->getTotalTax();
	}
}

//store validation errors
$errors = [];

//check for post
if (isset($_POST['tax_calculator'])) {		
	$requiredFields = ['country', 'cost', 'type'];
	foreach ($requiredFields as $field) {
		if (!isset($_POST[$field]) || ($_POST[$field] == '')) {			
			$errors[] = $field;
		}
	}
	if (!isset($_POST['cost']) || !is_numeric($_POST['cost']))	 {
		$errors[] = 'cost';
	}
	//engine cc required when engine type is fuel
	if ($_POST['type'] == 'fuel') {
		if (!isset($_POST['engine_cc']) || ($_POST['engine_cc'] == '' || !is_numeric($_POST['engine_cc']))) {
			$errors[] = 'engine_cc';
		}
	}
	if (empty($errors)) {
		$cost = intval($_POST['cost']);
		$country = in_array($_POST['country'], ['india', 'third']) ? $_POST['country'] : 'third';	
		$car = new Car($cost, $country);
		$engineType = in_array($_POST['type'], ['fuel', 'hybrid', 'electric']) ? $_POST['type'] : 'fuel';
		$engineCapacity = $engineType == 'fuel' ? intval($_POST['engine_cc']) : 0; //set 0 if engine set to other than fuel
		$car->setEngine(new Engine($engineCapacity, $engineType));
		$tax = new TaxCalculator($car);
		$tax->computeTax();
	}
}

?>

<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Bhutan Car Tax Calculator - Sales Tax and Customs Duty <?= date('Y'); ?></title>
		<meta name="description" value="Bhutan Car Tax Calculator for revised vehicle import taxes 2014.">
		<link rel="stylesheet" href="../css/bootstrap.min.css">
		<meta property="og:image" content="https://www.damchey.com/tools/images/car-tax-calculator.jpg" />
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		  ga('create', 'UA-63264676-1', 'auto');
		  ga('send', 'pageview');
		</script>
	</head>
	<body>		
	<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.3&appId=302666589934689";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>
		<div class="container">
			<div class="jumbotron">
				<h1 class="text-center">Bhutan Car Tax Calculator</h1>
				<p>
					This little utility calculates the car import taxes in Bhutan applicable since July 2014.
				</p>
				<p>
					The revised taxes are based on the cubic capacity and the type of the engine. A total of three different taxes (sales tax, customs duty & green tax) are applied on 
					the cost price of the vehicle. Electric battery operated cars do not attract any taxes and cars imported from India do not attract customs duty.
				</p>
				<p>
					As the rates are based off of figures mentioned in a Kuensel report, I cannot guarantee the accuracy of the calculation.
				</p>
				<div class="text-center">
					<div class="fb-like" data-href="https://www.damchey.com/tools/car-tax-calculator/" data-layout="button_count" data-action="like" data-show-faces="false" data-share="true"></div>
				</div>				
			</div>
	
			<div class="row">
				<div class="col-md-4<?= !isset($car) ? ' col-md-offset-4' : ''; ?>">
					<?php if(!empty($errors)): ?>
						<div class="alert alert-danger">
							There's something wrong with your input. Please correct them and try again.
						</div>
					<?php endif; ?>
					<form method="POST" class="form">
					<input type="hidden" name="tax_calculator" value="1">
					<div class="form-group<?= in_array('country', $errors) ? ' has-error' : ''; ?>">
						<label for="country">Country of Import</label>
						<select name="country" id="country" class="form-control">
							<option value="third" <?= (isset($_POST['country']) && $_POST['country'] == 'third') ? 'selected' : ''; ?>>Third Country</option>
							<option value="india" <?= (isset($_POST['country']) && $_POST['country'] == 'india') ? 'selected' : ''; ?>>India</option>
						</select>
					</div>
					<div class="form-group<?= in_array('cost', $errors) ? ' has-error' : ''; ?>">
						<label for="cost">Cost of vehicle (in Nu. before any taxes)</label>
						<input type="text" class="form-control" name="cost" value="<?= isset($_POST['cost']) ? $_POST['cost'] : ''; ?>">
					</div>
					<div class="form-group<?= in_array('engine_cc', $errors) ? ' has-error' : ''; ?>">
						<label for="engine_cc">Engine Capacity (cc)</label>
						<input type="text" class="form-control" name="engine_cc" value="<?= isset($_POST['engine_cc']) ? $_POST['engine_cc'] : ''; ?>">
					</div>
					<div class="form-group<?= in_array('type', $errors) ? ' has-error' : ''; ?>">
						<label for="type">Engine Type</label>
						<select name="type" id="type" class="form-control">
							<option value="fuel" <?= (isset($_POST['type']) && $_POST['type'] == 'fuel') ? 'selected' : ''; ?>>Fossil Fuel (Petrol/Diesel)</option>
							<option value="electric" <?= (isset($_POST['type']) && $_POST['type'] == 'electric') ? 'selected' : ''; ?>>Electric (Battery)</option>
							<option value="hybrid" <?= (isset($_POST['type']) && $_POST['type'] == 'hybrid') ? 'selected' : ''; ?>>Hybrid (Battery + Fossil Fuel)</option>
						</select>
					</div>
					<div class="form-group">
						<button class="btn btn-primary" type="submit">Calculate Tax</button>
					</div>
				</form>
				</div>
				<?php if(isset($car)): ?>				
					<div class="col-md-4">
						<h3 class="text-center">Full Taxes & Duties</h3>
						<table class="table table-striped table-bordered">
							<tr>
								<td>Sales Tax (<?= $tax->getSalesTaxPercentage(); ?>%)</td>
								<td class="text-right">Nu. <?= number_format($tax->getSalesTax(), 2); ?></td>
							</tr>
							<tr>
								<td>Customs Duty (<?= $tax->getCustomsDutyPercentage(); ?>%)</td>
								<td class="text-right">Nu. <?= number_format($tax->getCustomsDuty(), 2); ?></td>
							</tr>
							<tr>
								<td>Green Tax (<?= $tax->getGreenTaxPercentage(); ?>%)</td>
								<td class="text-right">Nu. <?= number_format($tax->getGreenTax(), 2); ?></td>
							</tr>
							<tr>
								<td>Total Tax</td><td class="text-right">Nu. <?= number_format($tax->getTotalTax(), 2); ?></td>
							</tr>
							<tr>
								<td>Base Price</td><td class="text-right">Nu. <strong><?= number_format($car->cost, 2) ?></strong></td>
							</tr>
							<tr>
								<td>Total Price</td><td class="text-right">Nu. <strong><?= number_format($tax->getTotalCost(), 2); ?></strong></td>
							</tr>							
						</table>
					</div>
					<?php $tax->setQuota(); ?>
					<div class="col-md-4">
						<h3 class="text-center">With Quota (800,000 ceiling)</h3>
						<table class="table table-striped table-bordered">
							<tr>
								<td>Sales Tax (<?= $tax->getSalesTaxPercentage(); ?>%)</td>
								<td class="text-right">Nu. <?= number_format($tax->getSalesTax(), 2); ?></td>
							</tr>
							<tr>
								<td>Customs Duty (<?= $tax->getCustomsDutyPercentage(); ?>%)</td>
								<td class="text-right">Nu. <?= number_format($tax->getCustomsDuty(), 2); ?></td>
							</tr>
							<tr>
								<td>Green Tax (<?= $tax->getGreenTaxPercentage(); ?>%)</td>
								<td class="text-right">Nu. <?= number_format($tax->getGreenTax(), 2); ?></td>
							</tr>
							<tr>
								<td>Total Tax</td><td class="text-right">Nu. <?= number_format($tax->getTotalTax(), 2); ?></td>
							</tr>
							<tr>
								<td>Base Price</td><td class="text-right">Nu. <strong><?= number_format($car->cost, 2) ?></strong></td>
							</tr>
							<tr>
								<td>Total Price</td><td class="text-right">Nu. <strong><?= number_format($tax->getTotalCost(), 2); ?></strong></td>
							</tr>							
						</table>
					</div>				
				<?php endif; ?>		
			</div><!-- /.row -->
		</div>
		<footer class="container" style="margin-top:30px">
			<div class="text-center" style="font-size:.9em">
				Written by <a href="https://www.damchey.com">Damchey</a> | Send feedback to damchey.lhendup@gmail.com
			</div>
		</footer>
	</body>
</html>