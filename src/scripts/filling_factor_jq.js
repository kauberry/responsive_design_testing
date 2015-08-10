$(function() {
  $('.form_container input[type="text"], .form_container textarea, .form_container select').delayedObserver(function(value,object){
    Calculate_Filling_Factor();
  },0.25);
  Calculate_Filling_Factor();
});

/* Program: Filling Factor Calculation
   Language: Java Script
   Author: Jamie Weaver
   Date: 02/14/2012
*/  


/* Calculate_Filling_Factor: This function converts numbers using the built-in 
   JavaScript "parseFloat" method.
   
   It then uses the form argument to output the sum of the
   numbers to the form's Answer field. Notice that the
   function does *not* need to know the the names of the
   form's input fields. Those values are passed as arguments.
   It does need to know that the form has a field named
   "Power" so that it can put the result there.

   Variable Names Key:

  F0    =   1H Frequency (MHz)
  F1    =   Test Frequency (MHz)
  Delta =   Frequency Shift (MHz)
  Q   =   (-7dB value)
  D   = Al Sphere Diameter (mm)
  Deg   = Housing Angle (degree)
  T90   = 90 pulse length (us)
  BF    = B1 homogeneity factor
  Vol   = Volume (calculated by this program)
  B0    = Constant, homogeneous Magnetic Field
  B1    = Variable Magnetic Field
  Deltaf  = 1H Frequency (MHz) calculation
  Sindeg  = Test frequency Calculation
  FF    = Filling Factor (calculated by this program)
  P   = Power (calculated by this program)
*/

function Calculate_Filling_Factor() {
  var F0 = parseFloat($('#input_F0').val());
  var Delta = parseFloat($('#input_Delta').val());
  var Q = parseFloat($('#input_Q').val());
  var BF = parseFloat($('#input_BF').val());
  var F1 = parseFloat($('#input_F1').val());
  var D = parseFloat($('#input_D').val());
  var Deg = parseFloat($('#input_Deg').val());
  var T90 = parseFloat($('#input_T90').val());
  
  
  
  //Calculate Volume [Vol]
  var Vol = (3.0/2.0) * Math.PI * 4.0 * Math.pow(D,3) / (3.0 * 8.0 * 1e8); /* Yields Volume in mm^3 */
  //var Vol = (4.0/3.0) * Math.PI * Math.pow((D/2),3); /* Yields Volume in mm^3 */

  //Calculate 1H Frequency [DeltaF]
  var DeltaF = 2 * Delta * Math.pow(BF,2) / F1; /* Yields frequency in MHz */
  
  //Calculate Filling Factor [FF]
  var FF = DeltaF / (Vol * 1e9); /* 1e9 converts from ? to ? */
 
  //Calculate the homogeneous magnetic field [B0]
  var B0 = 7.05 * F0 / 300; /* Gives the static mag field for a given frequency */
  /* a 300 MHz NMR operates at 7.05 T. This is just a linear calculation based on that ratio */

  //Calculate the test frequency [Sindeg]
  var Sindeg = Math.sin(Deg * Math.PI / 180);
  
  //Calculate variable magnetic field
  var B1 = B0 / (4 * T90 * F1 * Sindeg);
  
  //Calculate Power
  var P = (2 * Math.PI * F1 * 1e5 * Vol * Math.pow(B1,2) * 1e7) / (4 * Math.PI * Q * DeltaF);
  
  $('#power_result_value').html(P.toFixed(2));

}