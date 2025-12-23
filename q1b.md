# Q2b – Strategy Pattern UML Explanation

## 📋 Overview

The Strategy Pattern is a behavioral design pattern that enables selecting an algorithm's behavior at runtime. It defines a family of algorithms, encapsulates each one, and makes them interchangeable.

---

## 1️⃣ Classes

### MovementStrategy (Interface / Abstract Class)

**Labeled:** `<<Strategy>>`

**Purpose:** This is the abstraction for all movement algorithms.

**Definition:**
```typescript
interface MovementStrategy {
    move(): void;
}
```

**Key Characteristics:**
- Defines a method: `+move(): void`
- Acts as the contract that all concrete strategies must implement
- Provides a common interface for interchangeable algorithms
- No implementation details - pure abstraction

---

### Concrete Strategies

#### StealthNavigation

**Inherits from:** `MovementStrategy`

**Purpose:** Implements movement using stealth logic.

**Definition:**
```typescript
class StealthNavigation implements MovementStrategy {
    move(): void {
        console.log("Moving silently and avoiding detection...");
        // Stealth-specific movement logic
        // - Reduced visibility
        // - Silent movement
        // - Path optimization for concealment
    }
}
```

**Key Characteristics:**
- Implements `+move()` using stealth logic
- Encapsulates stealth movement behavior
- Can be swapped with other strategies at runtime

---

#### HighSpeedTransit

**Inherits from:** `MovementStrategy`

**Purpose:** Implements movement using high-speed logic.

**Definition:**
```typescript
class HighSpeedTransit implements MovementStrategy {
    move(): void {
        console.log("Moving at maximum speed...");
        // High-speed movement logic
        // - Maximum velocity
        // - Direct path
        // - Energy consumption optimization
    }
}
```

**Key Characteristics:**
- Implements `+move()` using high-speed logic
- Encapsulates fast movement behavior
- Can be swapped with other strategies at runtime

**Note:** These concrete strategies encapsulate different movement behaviors, making them interchangeable without modifying the Entity class.

---

### Entity (Context)

**Labeled:** `<<Context>>`

**Purpose:** The Entity delegates the move action to its strategy, so it doesn't need to know the exact algorithm.

**Definition:**
```typescript
class Entity {
    // Aggregation/has-a relationship
    private strategy: MovementStrategy;

    constructor(strategy: MovementStrategy) {
        this.strategy = strategy;
    }

    // Allows changing movement behavior at runtime
    setStrategy(strategy: MovementStrategy): void {
        this.strategy = strategy;
        console.log("Strategy changed to:", strategy.constructor.name);
    }

    // Delegates movement to the current strategy
    move(): void {
        this.strategy.move();
    }
}
```

**Attributes:**
- `-strategy: MovementStrategy` (aggregation/has-a relationship)

**Methods:**
- `+setStrategy(strategy: MovementStrategy): void` → Allows changing movement behavior at runtime
- `+move(): void` → Delegates movement to the current strategy

**Key Characteristics:**
- Contains a reference to a MovementStrategy object
- Delegates work to the strategy object instead of implementing it directly
- Can change strategies dynamically at runtime
- Doesn't need to know the specific implementation details

---

## 2️⃣ Relationships

### UML Diagram Representation

```
┌─────────────────────────────────────┐
│     <<Strategy>>                     │
│   MovementStrategy                   │
├─────────────────────────────────────┤
│ +move(): void                        │
└──────────────┬───────────────────────┘
               │
               │ implements
               │
       ┌───────┴────────┐
       │                │
       ▼                ▼
┌──────────────┐  ┌──────────────────┐
│ StealthNav   │  │ HighSpeedTransit │
├──────────────┤  ├──────────────────┤
│ +move():void │  │ +move():void     │
└──────────────┘  └──────────────────┘
       ▲                ▲
       │                │
       │                │
       └────────┬───────┘
                │ uses
                │
       ┌────────▼────────┐
       │   <<Context>>   │
       │     Entity      │
       ├─────────────────┤
       │ -strategy:      │
       │  MovementStrategy│
       ├─────────────────┤
       │ +setStrategy()  │
       │ +move()         │
       └─────────────────┘
```

### Relationship Types

1. **Inheritance (Generalization)**
   - **StealthNavigation** → **MovementStrategy** (implements)
   - **HighSpeedTransit** → **MovementStrategy** (implements)
   - Represented by solid arrows with triangular arrowheads
   - Shows "is-a" relationship

2. **Aggregation/Association**
   - **Entity** → **MovementStrategy** (has-a relationship)
   - Represented by a line with a diamond (aggregation) or arrow (association)
   - Shows "uses" or "has-a" relationship
   - Entity contains a reference to MovementStrategy

3. **Dependency**
   - Entity depends on MovementStrategy interface
   - Allows runtime flexibility: Entity can swap strategies dynamically via `setStrategy()`

---

## 3️⃣ Key Points to Explain

### ✅ This is a Classic Strategy Pattern Example

The Strategy Pattern is one of the most commonly used design patterns in software engineering. It's particularly useful when:
- You have multiple ways to perform a task
- You want to avoid conditional statements for selecting algorithms
- You need runtime flexibility in algorithm selection

### ✅ Decouples Movement Behavior from the Entity

**Benefits:**
- Entity doesn't need to know implementation details
- Movement logic is separated from Entity logic
- Changes to movement algorithms don't affect Entity class
- Follows Single Responsibility Principle

**Example:**
```typescript
// Entity doesn't care HOW movement happens, just THAT it happens
const entity = new Entity(new StealthNavigation());
entity.move(); // Uses stealth logic

entity.setStrategy(new HighSpeedTransit());
entity.move(); // Now uses high-speed logic
```

### ✅ Adding New Movement Types Doesn't Require Modifying Entity

**Open/Closed Principle:**
- Open for extension (new strategies)
- Closed for modification (Entity class unchanged)

**Example:**
```typescript
// New strategy - no changes to Entity needed!
class Teleportation implements MovementStrategy {
    move(): void {
        console.log("Teleporting instantly...");
    }
}

// Just use it!
entity.setStrategy(new Teleportation());
entity.move();
```

### ✅ Runtime Flexibility

The entity can switch strategies depending on:
- **Terrain:** Stealth in forests, high-speed on roads
- **Context:** Enemy nearby → stealth, safe area → high-speed
- **Resources:** Low energy → stealth, full energy → high-speed
- **Mission type:** Infiltration → stealth, pursuit → high-speed

**Example:**
```typescript
class Entity {
    private strategy: MovementStrategy;

    moveBasedOnContext(context: string): void {
        if (context === "enemy_nearby") {
            this.setStrategy(new StealthNavigation());
        } else if (context === "open_field") {
            this.setStrategy(new HighSpeedTransit());
        }
        this.move();
    }
}
```

### ✅ Perfect for Exam Questions

This pattern is ideal for questions asking about:
- **Interchangeable behavior:** "Design a pattern for behaviors that can be swapped"
- **Algorithm selection:** "How to select algorithms at runtime?"
- **Decoupling:** "How to separate algorithm from context?"
- **Extensibility:** "How to add new behaviors without modifying existing code?"

---

## 4️⃣ Complete Code Example

### TypeScript Implementation

```typescript
// Strategy Interface
interface MovementStrategy {
    move(): void;
}

// Concrete Strategy 1
class StealthNavigation implements MovementStrategy {
    move(): void {
        console.log("🔇 Moving silently...");
        console.log("   - Reduced visibility");
        console.log("   - Silent footsteps");
        console.log("   - Concealed path");
    }
}

// Concrete Strategy 2
class HighSpeedTransit implements MovementStrategy {
    move(): void {
        console.log("⚡ Moving at maximum speed...");
        console.log("   - Maximum velocity");
        console.log("   - Direct path");
        console.log("   - Energy consumption");
    }
}

// Concrete Strategy 3 (Example of easy extension)
class Teleportation implements MovementStrategy {
    move(): void {
        console.log("✨ Teleporting instantly...");
        console.log("   - Instantaneous movement");
        console.log("   - High energy cost");
    }
}

// Context Class
class Entity {
    private strategy: MovementStrategy;

    constructor(strategy: MovementStrategy) {
        this.strategy = strategy;
    }

    setStrategy(strategy: MovementStrategy): void {
        this.strategy = strategy;
        console.log(`\n🔄 Strategy changed to: ${strategy.constructor.name}\n`);
    }

    move(): void {
        this.strategy.move();
    }
}

// Usage Example
function demonstrateStrategyPattern() {
    console.log("=== Strategy Pattern Demonstration ===\n");

    // Create entity with initial strategy
    const entity = new Entity(new StealthNavigation());
    
    console.log("Initial movement:");
    entity.move();

    // Change strategy at runtime
    entity.setStrategy(new HighSpeedTransit());
    entity.move();

    // Add new strategy without modifying Entity
    entity.setStrategy(new Teleportation());
    entity.move();

    // Context-based strategy selection
    console.log("\n=== Context-Based Strategy Selection ===");
    const context = "enemy_nearby";
    
    if (context === "enemy_nearby") {
        entity.setStrategy(new StealthNavigation());
    } else {
        entity.setStrategy(new HighSpeedTransit());
    }
    entity.move();
}

// Run demonstration
demonstrateStrategyPattern();
```

### Output:
```
=== Strategy Pattern Demonstration ===

Initial movement:
🔇 Moving silently...
   - Reduced visibility
   - Silent footsteps
   - Concealed path

🔄 Strategy changed to: HighSpeedTransit

⚡ Moving at maximum speed...
   - Maximum velocity
   - Direct path
   - Energy consumption

🔄 Strategy changed to: Teleportation

✨ Teleporting instantly...
   - Instantaneous movement
   - High energy cost

=== Context-Based Strategy Selection ===

🔄 Strategy changed to: StealthNavigation

🔇 Moving silently...
   - Reduced visibility
   - Silent footsteps
   - Concealed path
```

---

## 5️⃣ UML Class Diagram (Detailed)

```
┌─────────────────────────────────────────────┐
│          <<Strategy>>                       │
│        MovementStrategy                     │
│         (Interface)                         │
├─────────────────────────────────────────────┤
│ +move(): void                               │
└──────────────────┬──────────────────────────┘
                   │
                   │ <<implements>>
                   │
        ┌──────────┴──────────┐
        │                      │
        ▼                      ▼
┌──────────────────┐  ┌──────────────────┐
│ StealthNavigation│  │HighSpeedTransit  │
│  (Concrete)       │  │  (Concrete)      │
├──────────────────┤  ├──────────────────┤
│ +move(): void     │  │ +move(): void    │
└──────────────────┘  └──────────────────┘
        ▲                      ▲
        │                      │
        │                      │
        └──────────┬───────────┘
                   │
                   │ <<uses>>
                   │ (aggregation)
        ┌──────────▼───────────┐
        │    <<Context>>       │
        │      Entity          │
        ├──────────────────────┤
        │ -strategy:           │
        │  MovementStrategy    │
        ├──────────────────────┤
        │ +setStrategy(        │
        │   strategy:          │
        │   MovementStrategy)  │
        │   : void             │
        │ +move(): void        │
        └──────────────────────┘
```

---

## 6️⃣ Design Principles Applied

### SOLID Principles

1. **Single Responsibility Principle (SRP)**
   - Each strategy class has one responsibility: implement one movement algorithm
   - Entity is responsible only for delegating movement

2. **Open/Closed Principle (OCP)**
   - Open for extension: Add new strategies without modifying Entity
   - Closed for modification: Entity class doesn't need changes

3. **Liskov Substitution Principle (LSP)**
   - Any MovementStrategy implementation can replace another
   - All strategies are interchangeable

4. **Interface Segregation Principle (ISP)**
   - MovementStrategy interface is focused and minimal
   - Only contains methods needed for movement

5. **Dependency Inversion Principle (DIP)**
   - Entity depends on MovementStrategy abstraction
   - Not on concrete implementations

---

## 7️⃣ When to Use Strategy Pattern

### ✅ Use When:
- You have multiple ways to perform a task
- You want to avoid conditional statements for algorithm selection
- You need runtime flexibility
- Algorithms should be interchangeable
- You want to isolate algorithm implementation details

### ❌ Don't Use When:
- You have only one way to perform a task
- Algorithms are not interchangeable
- The overhead of additional classes is not justified
- Strategy selection is compile-time only

---

## 8️⃣ Advantages & Disadvantages

### ✅ Advantages:
1. **Runtime Flexibility:** Change behavior dynamically
2. **Decoupling:** Separates algorithm from context
3. **Extensibility:** Easy to add new strategies
4. **Eliminates Conditionals:** No need for if/switch statements
5. **Testability:** Each strategy can be tested independently

### ❌ Disadvantages:
1. **Increased Complexity:** More classes and interfaces
2. **Client Awareness:** Client must know about different strategies
3. **Communication Overhead:** Strategy and context must communicate
4. **Over-engineering:** May be overkill for simple scenarios

---

## 9️⃣ Real-World Examples

1. **Payment Processing:** Credit card, PayPal, Bank transfer strategies
2. **Compression Algorithms:** ZIP, RAR, 7Z strategies
3. **Sorting Algorithms:** QuickSort, MergeSort, BubbleSort strategies
4. **Navigation:** Walking, Driving, Flying strategies
5. **Rendering:** 2D, 3D, Vector rendering strategies

---

## 🔟 Exam Tips

### Common Questions:
1. **"Design a pattern for interchangeable behaviors"** → Strategy Pattern
2. **"How to select algorithms at runtime?"** → Strategy Pattern
3. **"How to avoid conditional statements for algorithm selection?"** → Strategy Pattern
4. **"Design pattern that follows Open/Closed Principle"** → Strategy Pattern

### Key Points to Mention:
- ✅ Decouples algorithm from context
- ✅ Runtime flexibility
- ✅ Easy to extend without modifying existing code
- ✅ Follows SOLID principles
- ✅ Eliminates conditional complexity

---

## 📚 Summary

The Strategy Pattern is a powerful design pattern that:
- **Encapsulates** algorithms in separate classes
- **Makes** algorithms interchangeable
- **Allows** runtime selection of algorithms
- **Decouples** algorithm implementation from context
- **Follows** SOLID principles, especially Open/Closed Principle

This pattern is perfect for scenarios where you need flexible, interchangeable behaviors that can be selected and changed at runtime without modifying the context class.

---

**Created for:** Educational purposes and exam preparation  
**Pattern Type:** Behavioral Design Pattern  
**Complexity:** Low to Medium  
**Usefulness:** High ⭐⭐⭐⭐⭐

