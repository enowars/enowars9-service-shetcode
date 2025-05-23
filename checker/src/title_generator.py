import random
import json
import string

adjectives = [
    "Chrono", "Temporal", "Quantum", "Retro", "Neo-", "Paradoxical", "Infinite", "Lost", "Forgotten", "Eternal",
    "Time-Warped", "Futuristic", "Prehistoric", "Anachronistic", "Dimensional", "Cosmic", "Galactic", "Stellar",
    "Mysterious", "Ancient", "Forbidden", "Legendary", "Ethereal", "Transcendent", "Omnipotent", "Spectral"
]
nouns = [
    "Voyager", "Rift", "Chronicle", "Expedition", "Odyssey", "Portal", "Paradox", "Nexus", "Traveler", "Guardian",
    "Chronometer", "Anomaly", "Epoch", "Continuum", "Diary", "Machine", "Artifact", "Crystal", "Device", "Gateway",
    "Compass", "Medallion", "Key", "Scroll", "Codex", "Engine", "Beacon", "Prism", "Sphere", "Vault"
]
time_periods = [
    "Jurassic Era", "Renaissance", "Victorian Age", "Roaring Twenties", "Year 3000", "Distant Future", "Big Bang",
    "Dawn of Civilization", "Age of Dinosaurs", "Cyberpunk Metropolis", "Epoch of Enlightenment", "Stone Age",
    "Wild West", "Ancient Egypt", "Medieval Times", "Steampunk Era", "Space Age", "Robot Revolution",
    "Ice Age", "Bronze Age", "Elizabethan Era", "Prohibition Era", "Swinging Sixties", "Dark Ages", "Golden Age",
    "Atomic Age", "Information Age", "Post-Apocalyptic Wasteland", "Interstellar Colonial Period"
]
actions = [
    "Escape from", "Return to", "Battle for", "Quest to", "Secrets of", "Rise of", "Fall of", "Chronicles of",
    "Mysteries of", "Journey through", "Discovery of", "Invention of", "Curse of", "Prophecy of", "Legend of",
    "Hunt for", "Search for", "Revenge of", "Awakening of", "Descent into", "Ascension to", "Liberation of"
]

time_travel_objects = [
    "DeLorean", "TARDIS", "time machine", "temporal portal", "quantum accelerator", "chronosphere", 
    "time crystal", "flux capacitor", "temporal anchor", "causality engine", "chronometer", "time vortex",
    "pocket watch", "time turner", "quantum tunnel", "wormhole generator", "temporal compass", "chrono suit",
    "time bubble", "dimensional gateway", "temporal displacement device", "quantum entanglement chamber"
]

scientists = [
    "Dr. Chronos", "Professor Timewell", "Dr. Quantum", "Professor Paradox", "Dr. Flux", "Professor Temporal",
    "Dr. Einstein", "Professor Curie", "Dr. Tesla", "Professor Newton", "Dr. Hawking", "Professor Schrödinger",
    "Dr. Spacetime", "Professor Continuum", "Dr. Causality", "Professor Timeline", "Dr. Relativity", "Professor Dimension",
    "Dr. Wibbly-Wobbly", "Professor Timey-Wimey", "Dr. Butterfly", "Professor Ripple", "Dr. Yesterday", "Professor Tomorrow"
]

locations = [
    "Area 51", "CERN", "secret laboratory", "university basement", "abandoned mansion", "government facility",
    "space station", "underground bunker", "ancient temple", "futuristic city", "parallel dimension", "quantum realm",
    "hidden cave", "lighthouse", "clock tower", "observatory", "submarine", "floating island", "crystal cavern",
    "interdimensional café", "temporal repair shop", "time traveler's garage", "chronology department", "timeline office"
]

problems_scenarios = [
    {
        "theme": "temporal_calculation",
        "description_template": "You are {scientist} working on a {object} in a {location}. You need to calculate the temporal displacement between different time periods to avoid creating paradoxes.",
        "problem_type": "Calculate time differences and temporal coordinates",
        "test_cases": [
            {"input": "1985 2015", "output": "30"},
            {"input": "1955 1885", "output": "70"},
            {"input": "2024 1776", "output": "248"},
            {"input": "1969 2000", "output": "31"},
            {"input": "1066 2024", "output": "958"}
        ]
    },
    {
        "theme": "timeline_sequence",
        "description_template": "While traveling through time with your {object}, you've accidentally scattered historical events across different timelines. Help {scientist} reorder them chronologically.",
        "problem_type": "Sort historical events by year",
        "test_cases": [
            {"input": "Moon Landing:1969 WWI:1914 Renaissance:1400", "output": "Renaissance:1400 WWI:1914 Moon Landing:1969"},
            {"input": "Dinosaur Extinction:65000000BC Roman Empire:27BC Industrial Revolution:1760", "output": "Dinosaur Extinction:65000000BC Roman Empire:27BC Industrial Revolution:1760"},
            {"input": "Internet:1990 Printing Press:1440 Fire Discovery:400000BC", "output": "Fire Discovery:400000BC Printing Press:1440 Internet:1990"},
            {"input": "First Flight:1903 Wheel Invention:3500BC Smartphone:2007", "output": "Wheel Invention:3500BC First Flight:1903 Smartphone:2007"}
        ]
    },
    {
        "theme": "paradox_prevention",
        "description_template": "Your {object} is malfunctioning! {scientist} warns that meeting yourself in the past could create a paradox. Calculate if two time travelers will meet.",
        "problem_type": "Determine if time travelers' paths intersect",
        "test_cases": [
            {"input": "1955-11-05 1955-11-12 1955-11-08 1955-11-10", "output": "PARADOX"},
            {"input": "1885-09-01 1885-09-05 1885-09-06 1885-09-10", "output": "SAFE"},
            {"input": "2015-10-21 2015-10-21 2015-10-21 2015-10-21", "output": "PARADOX"},
            {"input": "1776-07-04 1776-07-10 1776-07-01 1776-07-03", "output": "SAFE"},
            {"input": "1969-07-20 1969-07-25 1969-07-22 1969-07-24", "output": "PARADOX"}
        ]
    },
    {
        "theme": "energy_calculation",
        "description_template": "The {object} requires 1.21 gigawatts of power per time jump. {scientist} needs to calculate total energy consumption for multiple jumps from {location}.",
        "problem_type": "Calculate total energy needed for time travel",
        "test_cases": [
            {"input": "3", "output": "3.63"},
            {"input": "5", "output": "6.05"},
            {"input": "1", "output": "1.21"},
            {"input": "7", "output": "8.47"},
            {"input": "10", "output": "12.1"}
        ]
    },
    {
        "theme": "temporal_coordinates",
        "description_template": "Your {object} uses coordinate system where each year is represented by its digits sum. {scientist} needs to convert years to temporal coordinates.",
        "problem_type": "Convert years to temporal coordinate system",
        "test_cases": [
            {"input": "1985", "output": "23"},
            {"input": "2024", "output": "8"},
            {"input": "1776", "output": "21"},
            {"input": "1969", "output": "25"},
            {"input": "2001", "output": "3"}
        ]
    },
    {
        "theme": "butterfly_effect",
        "description_template": "{scientist} discovered that changing N events in the past creates 2^N timeline branches. Calculate how many timelines exist after making changes.",
        "problem_type": "Calculate exponential timeline branches",
        "test_cases": [
            {"input": "3", "output": "8"},
            {"input": "5", "output": "32"},
            {"input": "0", "output": "1"},
            {"input": "4", "output": "16"},
            {"input": "6", "output": "64"}
        ]
    },
    {
        "theme": "time_loop_detection",
        "description_template": "The {object} has trapped {scientist} in a time loop at {location}! Detect if a sequence of timestamps represents a repeating pattern.",
        "problem_type": "Detect repeating time patterns",
        "test_cases": [
            {"input": "9:00 10:00 11:00 9:00 10:00 11:00", "output": "LOOP"},
            {"input": "8:30 9:15 10:45 11:20 12:00", "output": "LINEAR"},
            {"input": "1:00 2:00 1:00 2:00 1:00", "output": "LOOP"},
            {"input": "12:00 1:00 2:00 3:00 4:00", "output": "LINEAR"},
            {"input": "6:00 7:00 8:00 6:00 7:00", "output": "LOOP"}
        ]
    },
    {
        "theme": "temporal_frequency",
        "description_template": "{scientist} needs to calibrate the {object} at {location}. Calculate the harmonic frequency needed to synchronize with the target time period.",
        "problem_type": "Calculate temporal resonance frequency",
        "test_cases": [
            {"input": "1985", "output": "0.5"},
            {"input": "2024", "output": "2.0"},
            {"input": "1776", "output": "1.5"},
            {"input": "1969", "output": "1.0"},
            {"input": "2001", "output": "2.5"}
        ]
    },
    {
        "theme": "causality_chain",
        "description_template": "Working with the {object} at {location}, {scientist} discovers that each action causes 3 more actions in the timeline. Calculate the total events after N initial actions.",
        "problem_type": "Calculate cascading temporal events",
        "test_cases": [
            {"input": "1", "output": "4"},
            {"input": "2", "output": "10"},
            {"input": "3", "output": "22"},
            {"input": "0", "output": "0"},
            {"input": "4", "output": "46"}
        ]
    },
    {
        "theme": "temporal_coordinates_advanced",
        "description_template": "The {object} at {location} uses a complex coordinate system. {scientist} needs to convert binary timestamps to decimal time codes.",
        "problem_type": "Convert binary time codes to decimal",
        "test_cases": [
            {"input": "1010", "output": "10"},
            {"input": "1111", "output": "15"},
            {"input": "1001", "output": "9"},
            {"input": "1100", "output": "12"},
            {"input": "0101", "output": "5"}
        ]
    },
    {
        "theme": "time_dilation",
        "description_template": "The {object} is experiencing time dilation! {scientist} at {location} needs to calculate how much time passes on Earth when N hours pass in the time machine.",
        "problem_type": "Calculate relativistic time dilation effects",
        "test_cases": [
            {"input": "1", "output": "24"},
            {"input": "2", "output": "48"},
            {"input": "5", "output": "120"},
            {"input": "0", "output": "0"},
            {"input": "10", "output": "240"}
        ]
    },
    {
        "theme": "temporal_password",
        "description_template": "The {object} is password protected! {scientist} discovers the password is the sum of digits in significant historical years. Help crack the code at {location}.",
        "problem_type": "Calculate historical year digit sums for temporal passwords",
        "test_cases": [
            {"input": "1969 1776", "output": "46"},
            {"input": "2001 1492", "output": "19"},
            {"input": "1945 1066", "output": "32"},
            {"input": "1989 1215", "output": "46"},
            {"input": "2024 1865", "output": "28"}
        ]
    },
    {
        "theme": "timeline_repair",
        "description_template": "Oh no! The {object} at {location} has corrupted the timeline! {scientist} needs to fix temporal anomalies by calculating missing sequence numbers.",
        "problem_type": "Find missing numbers in temporal sequences",
        "test_cases": [
            {"input": "1 2 4 5", "output": "3"},
            {"input": "10 20 40 50", "output": "30"},
            {"input": "5 10 20 25", "output": "15"},
            {"input": "100 200 400 500", "output": "300"},
            {"input": "2 4 8 10", "output": "6"}
        ]
    }
]

def generate_title():
    templates = [
        "{adj} {noun}",
        "{action} the {period}",
        "{adj} {noun}: {action} the {period}",
        "The {noun} of the {period}",
        "{action} the {adj} {noun}",
        "{adj} {noun} in the {period}",
    ]
    template = random.choice(templates)

    title = template.format(
        adj=random.choice(adjectives),
        noun=random.choice(nouns),
        action=random.choice(actions),
        period=random.choice(time_periods)
    )
    return title

def generate_description():
    scenario = random.choice(problems_scenarios)
    description = scenario["description_template"].format(
        scientist=random.choice(scientists),
        object=random.choice(time_travel_objects),
        location=random.choice(locations)
    )
    description += f"\n\nTask: {scenario['problem_type']}"
    
    flavor_texts = [
        "\n\nRemember: The timeline depends on your calculations!",
        "\n\nWarning: Incorrect calculations may cause temporal anomalies!",
        "\n\nNote: The fate of the space-time continuum is in your hands!",
        "\n\nCaution: One wrong calculation could unravel reality itself!",
        "\n\nImportant: Time waits for no one, but your code must be precise!",
        "\n\nAlert: Paradoxes detected! Your algorithm is our only hope!",
        "\n\nCritical: The grandfather paradox is imminent! Code quickly!",
        "\n\nUrgent: Timeline collapse in T-minus... well, time is relative!",
        "\n\nInfo: In case of paradox, blame the butterfly effect!",
        "\n\nTip: When in doubt, add more flux capacitors!"
    ]
    description += random.choice(flavor_texts)
    
    return description

def generate_test_cases_and_outputs():
    scenario = random.choice(problems_scenarios)
    test_cases = scenario["test_cases"]
    
    selected_cases = random.sample(test_cases, min(len(test_cases), random.randint(4, 5)))
    
    inputs = [case["input"] for case in selected_cases]
    outputs = [case["output"] for case in selected_cases]
    
    return json.dumps(inputs), json.dumps(outputs)

def generate_problem_from_scenario(scenario=None):
    if scenario is None:
        scenario = random.choice(problems_scenarios)
        
    description = scenario["description_template"].format(
        scientist=random.choice(scientists),
        object=random.choice(time_travel_objects),
        location=random.choice(locations)
    )
    description += f"\n\nTask: {scenario['problem_type']}"
    
    flavor_texts = [
        "\n\nRemember: The timeline depends on your calculations!",
        "\n\nWarning: Incorrect calculations may cause temporal anomalies!",
        "\n\nNote: The fate of the space-time continuum is in your hands!",
        "\n\nCaution: One wrong calculation could unravel reality itself!",
        "\n\nImportant: Time waits for no one, but your code must be precise!",
        "\n\nAlert: Paradoxes detected! Your algorithm is our only hope!",
        "\n\nCritical: The grandfather paradox is imminent! Code quickly!",
        "\n\nUrgent: Timeline collapse in T-minus... well, time is relative!",
        "\n\nInfo: In case of paradox, blame the butterfly effect!",
        "\n\nTip: When in doubt, add more flux capacitors!",
        "\n\nNote: Wibbly-wobbly, timey-wimey... stuff!",
        "\n\nWarning: Side effects may include temporal headaches!",
        "\n\nReminder: No time travelers were harmed in making this problem!",
        "\n\nFun fact: This problem is approved by the Time Lords!",
        "\n\nDisclaimer: Results may vary across parallel universes!"
    ]
    description += random.choice(flavor_texts)
    
    test_cases = scenario["test_cases"]
    selected_cases = random.sample(test_cases, min(len(test_cases), random.randint(4, 5)))
    
    inputs = [case["input"] for case in selected_cases]
    outputs = [case["output"] for case in selected_cases]
    
    return description, json.dumps(inputs), json.dumps(outputs)

def generate_complete_problem():
    description, test_cases, expected_outputs = generate_problem_from_scenario()
    return {
        "title": generate_title(),
        "description": description,
        "test_cases": test_cases,
        "expected_outputs": expected_outputs
    }

def generate_funny_username():
    funny_prefixes = [
        "TimeGuy", "ChronoNerd", "FluxBoy", "TARDIS", "DrWho", "BackTo", "TimeLoop", "Paradox",
        "QuantumCat", "FluxLord", "TimeDude", "ChronoKid", "WarpZone", "TimeVibe", "FluxFan",
        "DeLorean", "TimeBro", "ChronoBot", "QuantumFox", "TimeGeek", "FluxHero", "WarpDude",
        "TimeMage", "ChronoElf", "QuantumBee", "TimeWolf", "FluxNinja", "WarpBear", "TimeFrog",
        "ChronoFish", "QuantumPig", "TimeLlama", "FluxDuck", "WarpOwl", "TimeBat", "ChronoDog",
        "QuantumApe", "TimeRat", "FluxBug", "WarpAnt", "TimeCow", "ChronoEgg", "QuantumTea",
        "TimePie", "FluxCake", "WarpPizza", "TimeTaco", "ChronoSoup"
    ]
    
    funny_suffixes = [
        "42", "88", "99", "101", "404", "2024", "1985", "3000", "007", "123",
        "X", "Z", "Q", "XL", "HD", "Pro", "Max", "Jr", "Sr", "II",
        "AI", "3D", "VR", "AR", "XP", "OS", "2K", "4K", "8K", "GO"
    ]
    
    time_adjectives = ["Time", "Flux", "Warp", "Chrono", "Quantum"]
    funny_nouns = ["Cat", "Dog", "Fox", "Bee", "Owl", "Frog", "Duck", "Bear", "Wolf", "Bat",
                   "Pie", "Cake", "Tea", "Soup", "Taco", "Guy", "Dude", "Nerd", "Geek", "Hero"]
    
    method = random.choice([1, 2, 3])
    
    if method == 1:
        prefix = random.choice(funny_prefixes)
        suffix = random.choice(funny_suffixes)
        username = f"{prefix}{suffix}"
    elif method == 2:
        adj = random.choice(time_adjectives)
        noun = random.choice(funny_nouns)
        num = random.randint(1, 999)
        username = f"{adj}{noun}{num}"
    else:
        part1 = random.choice(["Time", "Flux", "Warp", "Dr", "Prof", "Chrono"])
        part2 = random.choice(["Cat", "Bot", "Guy", "Fox", "Bee", "Owl", "Ace"])
        num = random.randint(10, 99)
        username = f"{part1}_{part2}{num}"

    username += "_".join(random.choices(string.ascii_letters, k=5))
    return username