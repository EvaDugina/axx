<?php

enum Param: string
{
    case OUTCOME = "outcome";
    case FULL_OUTPUT = "full_output";
    case ERROR = "error";
    case FAILED = "failed";
    case CHECK_NAME = "check";
    case RESULT = "result";
    case PASSED = "passed";
    case SECONDS = "seconds";
}
