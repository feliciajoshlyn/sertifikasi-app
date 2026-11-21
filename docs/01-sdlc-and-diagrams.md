# Program Specifications & System Design (J.620100.009.02)

## 1. SDLC Methodology Selection & Justification
- **Selected Methodology:** Iterative & Incremental Development
- **Justification:** Allows building the application in small, testable cycles (Database → Business Logic → UI) while continuously refining features based on case study requirements.
- **Verification vs. Validation:**
  - **Verification (*"Are we building the product right?"*):** Static checks ensuring design, diagrams, and code follow specifications and standards before execution (e.g., ERD review, PSR-12 static checks).
  - **Validation (*"Are we building the right product?"*):** Dynamic testing executing the software to confirm it satisfies user/case study requirements (e.g., PHPUnit tests, browser UAT).
  - **Order:** Verification is performed **before** Validation so design and logic flaws are caught early before running dynamic tests.

---

## 2. System Flowchart
*(Replace the placeholder logic below with your D-Day case study flow. Ensure every branch terminates at `END` and includes error handling.)*

```mermaid
flowchart TD
    Start([START]) --> Init{{"Initialize: $inputData,$errors = []"}}
    Init --> Input[/"Input: User submits form data"/]
    Input --> Validate{"Is input valid?"}
    Validate -- False --> ErrorOut[/"Output: Return validation error"/]
    ErrorOut --> End([END])
    Validate -- True --> Process["Process: Save record via Service layer"]
    Process --> SuccessOut[/"Output: Display success message"/]
    SuccessOut --> End